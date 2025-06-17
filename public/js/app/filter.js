/**
 * Vvveb
 *
 * Copyright (C) 2021  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * https://github.com/givanz/Vvveb
 */

function filterChange() {
	let filters = {};
	let filterText = '';
	let action = window.location.href;
	let params = action.match(/\/\d+|\?/)
	if (params) {
		action = action.slice(0, params.index);
	}
	
	document.querySelectorAll('.filters input:checked').forEach(element => {
		let name = element.name.replace('[]','');
		if (typeof filters[name] == 'undefined') filters[name] = [];
		filters[name].push(element.value);
	});
	
	if ('URLSearchParams' in window) {
		let params = new URLSearchParams(window.location.search);
		for (const [key, value] of params.entries()) {
			if (key.startsWith("filter")) {params.delete(key)}
		}
		
		let query = params.toString();
		filterText = (query ? "?" + query : "");
	}
	
	for(const filterName in filters) {
		for (const filter in filters[filterName]) {   
			filterText += (filterText ? '&' : '?') + "filter[" + filterName + "][]=" + filters[filterName][filter];
		}
	}

	let url = action + filterText;
	let selector = VvvebTheme.ajax.siteContainer ?? "#site-content";
	loadAjax(url, selector);
	window.history.pushState({url, selector}, null, url);
	//location = action + filterText;
}	

let _filter_timeout;


document.addEventListener("click", (event) => {
  if (event.target.closest('.filters input')) {
	clearTimeout(_filter_timeout);
	_filter_timeout = setTimeout(function () {
		filterChange();
	}, 1000);
  }
});


/*
document.addEventListener("click", (event) => {
  let target = event.target.closest('.page-link');
  if (target) {
	let url = target.href;
	let selector = "#site-content";
	loadAjax(url, selector, () => {
		//let target = document.querySelector(selector);
		let target = document.querySelector("body");
		target.scrollIntoView({behavior: "smooth", block: "start", inline: "start"});
	});
	window.history.pushState({url, selector}, null, url);
	event.preventDefault();
  }
});
*/

/*
const rangeInput = document.querySelectorAll(".range-input input"),
priceInput = document.querySelectorAll(".price-input input"),
range = document.querySelector(".slider .progress");
let priceGap = 1000;

priceInput.forEach(input =>{
    input.addEventListener("input", e =>{
        let minPrice = parseInt(priceInput[0].value),
        maxPrice = parseInt(priceInput[1].value);
        
        if((maxPrice - minPrice >= priceGap) && maxPrice <= rangeInput[1].max){
            if(e.target.className === "input-min"){
                rangeInput[0].value = minPrice;
                range.style.left = ((minPrice / rangeInput[0].max) * 100) + "%";
            }else{
                rangeInput[1].value = maxPrice;
                range.style.right = 100 - (maxPrice / rangeInput[1].max) * 100 + "%";
            }
        }
    });
});

rangeInput.forEach(input =>{
    input.addEventListener("input", e =>{
        let minVal = parseInt(rangeInput[0].value),
        maxVal = parseInt(rangeInput[1].value);

        if((maxVal - minVal) < priceGap){
            if(e.target.className === "range-min"){
                rangeInput[0].value = maxVal - priceGap
            }else{
                rangeInput[1].value = minVal + priceGap;
            }
        }else{
            priceInput[0].value = minVal;
            priceInput[1].value = maxVal;
            range.style.left = ((minVal / rangeInput[0].max) * 100) + "%";
            range.style.right = 100 - (maxVal / rangeInput[1].max) * 100 + "%";
        }
    });
});
*/
