var stats = require('./statistics.js');
var db_reviews = require('./reviews_all.json');
// var db_workshop = require('./reviews_workshops.json');
var db_props = require('./reviews_proposals.json');

///// FUNCTIONS /////

// grab only table data
function processData(array){
	var reviews = array
		.filter(a => a.type == "table");
	return reviews[0].data;
};

// trim each row to only event, id', reviewer, review; push object to array
function trimScores(array, statusObject) {
	var scoreArray = [];
	for(let i = 0; i < array.length; i++){
		let current = array[i];
		let id = array[i]['prop_id'];

		let review = current.review.split('|').map(s => parseInt(s));

		let status = statusObject[id];


		scoreArray.push({
			event: current.event,
			id: current.prop_id,
			reviewer: current.reviewer,
			review,
			status
		});
	};

	return scoreArray;
};

// returns object with event as key; contains array of objects
function createPropObj(array){
	var eventObj = {};
	// create object for each id
	var obj = array.reduce((acc, cur) => {
		if(!acc[cur.id]) { 
			acc[cur.id] = {
				type: '',
				id: cur.id,
				scores: [],
				raters: [],
				mean: 0,
				stdev: 0,
				status: cur.status
			};
		};
		let key = cur.id;
		acc[key].type = cur.event;
		// grabs last number from string, which is total
		var score = cur.review.length > 1 ? cur.review[cur.review.length - 1] : cur.review[0];
		acc[key].scores.push(parseInt(score));
		acc[key].raters.push(cur.reviewer);
		return acc;
	},{});

	// iterate over object to add score stats
	for (var id in obj){
		let scores = obj[id].scores;
		if(scores){
			obj[id].mean = parseInt(stats.mean(scores, 1));
			obj[id].stdev = parseInt(stats.stanDev(scores, 1));	
		};		
	};

	// iterate over object to add review objects
	for(var id in obj){
		let event = obj[id].type
		if(eventObj[event] == undefined) {
			eventObj[event] = {
				reviews: [],
				count: 0
			};
		};
		eventObj[event].reviews.push(obj[id]);
		eventObj[event].count++;
	};

	// iterate over eventObj to add statistics
	for(var event in eventObj){
		let reviewArr = eventObj[event].reviews;
		eventObj[event].statistics = createStatsObj(reviewArr);
	};

	return eventObj;
};

// returns object with statistical information
function createStatsObj(array){
	var meansArr = [];
	var stdevsArr = [];
	var acceptedArr = [];
	var rejectedArr = [];

	array.forEach(obj => {
		if(obj.status == 'accepted'){
			acceptedArr.push(parseInt(obj.mean));
		} else if (obj.status == 'rejected'){
			rejectedArr.push(parseInt(obj.mean));
		};
		meansArr.push(parseInt(obj.mean));
		stdevsArr.push(parseInt(obj.stdev));
	});

	var mean_of_means = parseInt(stats.mean(meansArr));
	var aboveMean = 0;
	meansArr.forEach(score => {
		if(score < mean_of_means) aboveMean++;
	});

	meansArr.sort((a,b) => a - b);
	acceptedArr.sort((a,b) => a - b);
	rejectedArr.sort((a,b) => a - b);

	return {
		total_count: meansArr.length,
		acceptance: `${(acceptedArr.length / meansArr.length * 100).toFixed(0)}%`,
		mean_of_means,
		accepted: {
			count: acceptedArr.length,
			mean: parseInt(stats.mean(acceptedArr)),
			high: acceptedArr[acceptedArr.length - 1],
			low: acceptedArr[0]
		},
		rejected: {
			count: rejectedArr.length,
			mean: parseInt(stats.mean(rejectedArr)),
			high: rejectedArr[rejectedArr.length - 1],
			low: rejectedArr[0],
		},
		median: parseInt(stats.median(meansArr)),
		high: meansArr[meansArr.length - 1],
		low: meansArr[0],
		range: meansArr[meansArr.length - 1] - meansArr[0],
		mean_of_stdevs: Number(stats.mean(stdevsArr)),
		aboveMean: `${(aboveMean / meansArr.length).toFixed(2) * 100}%`
	};
};

function findMatches(array){
	let props = '';
	let idObj = {
		tech_fair: [],
		workshop: []
	};

	for(var i in array){
		if(array[i].event == "Mini-Workshops"){
			idObj.workshop.push(array[i].id);
		} else if(array[i].event == "Technology Fairs"){
			idObj.tech_fair.push(array[i].id);
		} else { continue; };
	};

	idObj.tech_fair.forEach(id => {
		if(idObj.workshop.includes(id)){ props += `${id} ` };
	});

	idObj.workshop.forEach(id => {
		if(idObj.tech_fair.includes(id)){ props += `${id} ` };
	});

	return props;
};

function findStatus(array){
	var result = {};
	for(let i = 0; i < array.length; i++){
		let status = array[i]['status'];
		let id = array[i]['id'];

		result[id] = status;
	};
	return result;
};

function printJSON(object, filter){
	for(var key in object){
		console.log(key, JSON.stringify(
			!filter ? object[key] : object[key][filter],
			null,
			4
			)
		);
	};
};

///// DATA /////

var table_props = processData(db_props);
var status_props = findStatus(table_props);

var table_reviews = processData(db_reviews); // array
var trimmed_reviews = trimScores(table_reviews, status_props); // array

var scoresObj = createPropObj(trimmed_reviews);

printJSON(scoresObj, 'statistics')
// console.log(JSON.stringify(scoresObj, null, 4));

