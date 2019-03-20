var stats = require('./statistics.js');
var db_reviews = require('./reviews_all.json');
var db_workshop = require('./reviews_workshops.json');

///// FUNCTIONS /////

// grab only table data
function processData(array){
	var reviews = array
		.filter(a => a.type == "table");
	return reviews[0].data;
};

// trim each row to only event, id', reviewer, review; push object to array
function trimScores(array) {
	var scoreArray = [];
	for(let i = 0; i < array.length; i++){
		let current = array[i];

		let review = current.review.split('|').map(s => parseInt(s));

		scoreArray.push({
			event: current.event,
			id: current.prop_id,
			reviewer: current.reviewer,
			review
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
				stdev: 0
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
	array.forEach(obj => {
		meansArr.push(parseInt(obj.mean));
		stdevsArr.push(parseInt(obj.stdev));
	});

	var mean_of_means = parseInt(stats.mean(meansArr));
	var aboveMean = 0;
	meansArr.forEach(score => {
		if(score < mean_of_means) aboveMean++;
	});

	meansArr = meansArr.sort((a,b) => a - b);

	return {
		count: meansArr.length,
		mean_of_means,
		median: parseInt(stats.median(meansArr)),
		high: meansArr[meansArr.length - 1],
		low: meansArr[0],
		range: meansArr[meansArr.length - 1] - meansArr[0],
		mean_of_stdevs: Number(stats.mean(stdevsArr)),
		aboveMean: `${(aboveMean / meansArr.length).toFixed(2) * 100}%`
	};
};

function findMatches(object){
	let props = '';
	let idObj = {
		tech_fair: [],
		workshop: []
	};

	for(var event in object){
		let currReviews = object[event].reviews;
		if(event == "Mini-Workshops"){
			for(let i = 0; i < currReviews.length; i++){
				idObj.workshop.push(currReviews[i].id);
			};
		} else if(event == "Technology Fairs"){
			for(let i = 0; i < currReviews.length; i++){
				idObj.tech_fair.push(currReviews[i].id);
			};
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

///// DATA /////

var table = processData(db_reviews); // array
var trimmed = trimScores(table); // array

var scoresObj = createPropObj(trimmed);
// var statsObj = createStatsObj(idScores);

// console.log(idScores);
// console.log(statsObj);
// for(var key in scoresObj){console.log(key, JSON.stringify(scoresObj[key], null, 4));};
// console.log(JSON.stringify(scoresObj, null, 4));
console.log(findMatches(scoresObj));
