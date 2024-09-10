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
 */

function filterChange() {
	let filters = {};
	let filter_text = '';
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
		filter_text = (query ? "?" + query : "");
	}
	
	for(filter_name in filters) {
			for (filter in filters[filter_name]) {   
				filter_text += (filter_text ? '&' : '?') + "filter[" + filter_name + "][]=" + filters[filter_name][filter];
			}
	}

	let url = action + filter_text;
	let selector = "#site-content";
	loadAjax(url, selector);
	window.history.pushState({url, selector}, null, url);
	//location = action + filter_text;
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
