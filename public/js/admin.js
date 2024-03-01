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
 
 
import {Router} from './common/router.js';
import {Themes} from './admin/controller/themes.js';
import {Plugins} from './admin/controller/plugins.js';
import {Table} from './admin/controller/table.js';
import {HeartBeat} from './admin/heartbeat.js';

if (window.Vvveb === undefined) window.Vvveb = {};

window.themes = Themes;
window.plugins = Plugins;
window.table = Table;

jQuery(document).ready(function() {
	Router.init();
});

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

            ajax.done(function() {
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

function displayToast(bg, title, message, id = "bottom-toast") {
	$("#" + id + " .toast-body .message").html(message);
	$("#" + id + " .toast-header").removeClass(["bg-danger", "bg-success"]).addClass(bg).
	find("strong").text(title);
	/*
	$("#" + id + " .toast").addClass("showing");
	delay(() => $("#" + id + " .toast").addClass("show").removeClass("showing"), 500);
	delay(() => $("#" + id + " .toast").removeClass("show"), 5000);*/
	let toast = new bootstrap.Toast(document.getElementById(id), {animation:false});
    toast.show()
}

window.displayToast = displayToast;
