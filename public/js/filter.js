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
	
	for(filter_name in filters) {
			for (filter in filters[filter_name]) {   
				filter_text += (filter_text ? '&' : '?') + "filter[" + filter_name + "][]=" + filters[filter_name][filter];
			}
	}
	
	location = action + filter_text;
}	

let _filter_timeout;

document.querySelectorAll('.filters input').forEach(e => e.addEventListener("click", function()  {
	clearTimeout(_filter_timeout);
	_filter_timeout = setTimeout(function () {
		filterChange();
	}, 1500);
}));