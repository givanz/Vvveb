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
 
import {postsComponent} from './posts.js';
import {categoriesComponent} from './categories.js';
import {archivesComponent} from './archives.js';
import {recentPostsComponent} from './recentPosts.js';
import {recentCommentsComponent} from './recentComments.js';
import {calendarComponent} from './calendar.js';
import {tagsComponent} from './tags.js';
import {menuComponent} from './menu.js';
import {userComponent} from './user.js';
import {languageComponent} from './language.js';
import {currencyComponent} from './currency.js';


Vvveb.Components.add("content/posts", postsComponent);
Vvveb.Components.add("content/categories", categoriesComponent);
Vvveb.Components.add("content/archives", archivesComponent);
//Vvveb.Components.add("content/recentPosts", recentPostsComponent);
//Vvveb.Components.add("content/recentComments", recentCommentsComponent);
Vvveb.Components.add("content/calendar", calendarComponent);
Vvveb.Components.add("content/tags", tagsComponent);
Vvveb.Components.add("content/menu", menuComponent);
Vvveb.Components.add("content/user", userComponent);
Vvveb.Components.add("content/language", languageComponent);
Vvveb.Components.add("content/currency", currencyComponent);


Vvveb.ComponentsGroup['Content'] = [
	"content/posts", 
	"content/categories",
	"content/archives",
//	"content/recentPosts",
//	"content/recentComments",
//	"content/calendar",
	"content/tags",
	"content/user",
	"content/language",
	"content/currency",
	"content/menu"
];
