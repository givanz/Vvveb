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
 
Vvveb.Undo = {
	
	mutations: [],
	undoIndex: -1,
	enabled:true,
	/*		
	init: function() {
	},
	*/	
	addMutation : function(mutation) {	
		/*
			this.mutations.push(mutation);
			this.undoIndex++;
		*/
		this.mutations.splice(++this.undoIndex, this.mutations.length - this.undoIndex, mutation);
		Vvveb.Builder.frameBody.trigger("vvveb.undo.add", mutation);
	 },

	restore : function(mutation, undo) {	
		
		switch (mutation.type) {
			case 'childList':
			
				if (undo == true)
				{
					addedNodes = mutation.removedNodes;
					removedNodes = mutation.addedNodes;
				} else //redo
				{
					addedNodes = mutation.addedNodes;
					removedNodes = mutation.removedNodes;
				}
				
				if (addedNodes) for(i in addedNodes)
				{
					node = addedNodes[i];
					if (mutation.nextSibling)
					{ 
						mutation.nextSibling.parentNode.insertBefore(node, mutation.nextSibling);
					} else
					{
						mutation.target.append(node);
					}
				}

				if (removedNodes) for(i in removedNodes)
				{
					node = removedNodes[i];
					node.parentNode.removeChild(node);
				}
			break;					
			case 'move':
				if (undo == true)
				{
					parent = mutation.oldParent;
					sibling = mutation.oldNextSibling;
				} else //redo
				{
					parent = mutation.newParent;
					sibling = mutation.newNextSibling;
				}
			  
				if (sibling)
				{
					sibling.parentNode.insertBefore(mutation.target, sibling);
				} else
				{
					parent.append(node);
				}
			break;
			case 'characterData':
			  mutation.target.innerHTML = undo ? mutation.oldValue : mutation.newValue;
			  break;
			case 'style':
			  $("#vvvebjs-styles", window.FrameDocument).html( undo ? mutation.oldValue : mutation.newValue );
			  break;
			case 'attributes':
			  value = undo ? mutation.oldValue : mutation.newValue;

			  if (value || value === false || value === 0)
				mutation.target.setAttribute(mutation.attributeName, value);
			  else
				mutation.target.removeAttribute(mutation.attributeName);

			break;
		}
		
		Vvveb.Builder.frameBody.trigger("vvveb.undo.restore", mutation);
	 },
	 
	undo : function() {	
		if (this.undoIndex >= 0) {
		  this.restore(this.mutations[this.undoIndex--], true);
		}
	 },

	redo : function() {	
		if (this.undoIndex < this.mutations.length - 1) {
		  this.restore(this.mutations[++this.undoIndex], false);
		}
	},

	hasChanges : function() {	
		return this.mutations.length;
	},

	reset : function() {	
		this.mutations = [];
		this.undoIndex = -1;
	}
};

