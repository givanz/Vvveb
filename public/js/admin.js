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
 
 
import {Router} from './common/router.js';
import {Themes} from './admin/controller/themes.js';
import {Plugins} from './admin/controller/plugins.js';
import {Table} from './admin/controller/table.js';
import {Cache} from './admin/controller/cache.js';
import {HeartBeat} from './admin/heartbeat.js';

if (window.Vvveb === undefined) window.Vvveb = {};

window.themes = Themes;
window.plugins = Plugins;
window.table = Table;
window.cache = Cache;

Router.init();

class AjaxStack {
    constructor() {
        this.start = 0;
        this.stack = [];
    }

    add(call) {
        this.stack.push(call);

        if (!this.start) {
            this.execute();
        }
    }

    execute() {
        if (this.start = this.stack.length) {
			let self = this;
            let call = this.stack.shift();
            let ajax = call();

            ajax.then(function() {
                self.execute();
            });
        }
    }
}

window.ajaxStack = new AjaxStack();

function ucFirst(str) {
  if (!str) return str;

  return str[0].toUpperCase() + str.slice(1);
}

function displayToast(type, title, message, position = 'bottom', id = null) {
	if (!id) {
		id = position + "-toast";
	}
	
	let toast = document.getElementById(id);
	let header = toast.querySelector(".toast-header");
	toast.classList.remove("bottom-0", "top-0");
	toast.classList.add(position + "-0");
	toast.querySelector(".toast-body .message").innerHTML = message;
	header.classList.remove("bg-danger", "bg-success");
	header.classList.add("bg-" + type);	
	header.querySelector("strong").textContent = title;
	let toastDisplay = toast.cloneNode(true);
	toast.parentNode.appendChild(toastDisplay);

	let delay = 3000;
	if (type == "danger") {
		delay = 20000;
	}

	let bsToast = new bootstrap.Toast(toastDisplay, {animation:true, delay});
	toastDisplay.addEventListener('hidden.bs.toast', () => {
		toastDisplay.remove();
	});
    bsToast.show();
}

window.displayToast = displayToast;

function generateElements(html) {
  const template = document.createElement('template');
  template.innerHTML = html.trim();
  return template.content.children;
}
