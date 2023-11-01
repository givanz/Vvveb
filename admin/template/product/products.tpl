import(listing.tpl, {"type":"product", "list": "products"})

[data-v-type_name_plural] = $this->type_name_plural
[data-v-type-name] 		  = $this->type_name
[data-v-type] 			  = $this->type
a[data-v-addurl]|href 	  = $this->addUrl

import(filters.tpl)