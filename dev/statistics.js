var sum = function (array){
	return array.reduce((a, c) => a + c, 0)
};

var median = function (array, decimal = 2){
	if(!array.length) throw new Error('List length is zero.')
	var answer;
	if(array.length % 2 !== 0){ 
		answer = array[Math.round(array.length / 2)] 
	} else {
		var low = array[(array.length / 2) - 1];
		var high = array[array.length / 2];
		answer = (low + high) / 2;
	};
	return Number(answer).toFixed(decimal);
};

var mean = function (array, decimal = 2){
	var total = array.reduce((a, c) => a + c, 0);
	return Number(total/array.length).toFixed(decimal);
};

var mode = function (array){
	var countMap = {};
	var grFreq = 0;
	var mode;
	array.forEach(n => {
		countMap[n] = (countMap[n] || 0) + 1
		if(grFreq < countMap[n]){
			grFreq = countMap[n];
			mode = n;
		};
	});
	return mode;
};

var range = function (array){
	array.sort();
	return (array[array.length - 1] - array[0]);
};

var stanDev = function (array, decimal = 2){
	var sqXlessXbar = array.map(num => Math.pow(num - mean(array), 2));
	return Math.sqrt( sum(sqXlessXbar) / array.length ).toFixed(decimal); 
};


module.exports = {
	sum,
	median,
	mean,
	mode,
	range,
	stanDev
};
