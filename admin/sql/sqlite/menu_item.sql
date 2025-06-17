-- Menus
	
	-- get menu 

	CREATE PROCEDURE getAll(

		-- variables
		IN  language_id INT,
		IN  menu_id INT,
		IN  site_id INT,
		IN  post_id INT,
		IN  slug CHAR,
		
		-- pagination
		IN start INT,
		IN limit INT,
			
		-- return array of menus for menus query
		OUT fetch_all,
		-- return menus count for count query
		-- OUT fetch_one
	)
	BEGIN

		SELECT td.*,menu_item.url, menu_item.sort_order, menu_item.parent_id, menu_item.type, menu_item.item_id, menu_item.menu_item_id as array_key
			
		
			FROM menu_item
		
			-- INNER JOIN menu_to_site c2s ON (menu.menu_id = c2s.menu_id AND c2s.site_id = :site_id) 
			INNER JOIN menu_item_content td ON (menu_item.menu_item_id = td.menu_item_id AND td.language_id = :language_id)  
			
			WHERE 
			
				td.language_id = :language_id -- AND c2s.site_id = :site_id
	
			
			@IF isset(:menu_id)
			THEN 
			
				AND menu_item.menu_id = :menu_id
				
			END @IF			
			
			@IF isset(:slug)
			THEN 
			
				AND menu_item.menu_id = (SELECT menu_id FROM menu WHERE slug = :slug LIMIT 1)
				
			END @IF			

		ORDER BY menu_item.parent_id, menu_item.sort_order, menu_item.menu_id

		@IF isset(:limit)
		THEN 
			@SQL_LIMIT(:start, :limit)
		END @IF		
		;

	END-- get all menus 	
	
	CREATE PROCEDURE getMenuAllLanguages(

		-- variables
		IN  user_group_id INT,
		IN  site_id INT,
		IN  menu_id INT,
		IN  search CHAR,
		
		-- pagination
		IN start INT,
		IN limit INT,
			
		-- return array of categories for categories query
		OUT fetch_all,
		-- return categories count for count query
		OUT fetch_one,
	)
	BEGIN

		SELECT *, 
			(
				SELECT 
				--	CONCAT('[', GROUP_CONCAT(
				--	'{"language_id":"', cd.language_id, 
				--		'","name":"' , cd.name, 
				--		'","slug":"' , cd.slug, 
				--		'","content":"' , cd.content, 
				--		'"}'), ']') 
					JSON_ARRAYAGG( JSON_OBJECT(
						'language_id', cd.language_id, 
						'name', cd.name, 
						'slug', cd.slug, 
						'content', cd.content)) 
						
					FROM menu_item_content as cd 
				WHERE 
					cd.menu_item_id = categories.menu_item_id GROUP BY cd.menu_item_id
			) as languages
			
		FROM menu_item AS categories
		
			WHERE 1 = 1
					
	
			@IF isset(:search)
			THEN 
			
				AND td.name LIKE :search
				
			END @IF						
			
			@IF isset(:menu_id)
			THEN 
			
				AND categories.menu_id = :menu_id
				
			END @IF			

		ORDER BY categories.parent_id, categories.sort_order, categories.menu_item_id
		
		@IF isset(:limit)
		THEN 		
			@SQL_LIMIT(:start, :limit)
		END @IF
		
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(categories.menu_item_id, menu) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;


	END
	


	-- Edit menu

	CREATE PROCEDURE editMenuItem(
		IN menu_item ARRAY,
		IN menu_item_id INT,
		OUT insert_id
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:menu_item_content_data = @FILTER(:menu_item.menu_item_content, menu_item_content)

		@EACH(:menu_item_content_data) 
			INSERT INTO menu_item_content 
		
				( @KEYS(:each), menu_item_id)
			
			VALUES ( :each, :menu_item_id)
				ON DUPLICATE KEY UPDATE @LIST(:each);

		-- allow only table fields and set defaults for missing values
		@FILTER(:menu_item, menu_item)
		
		UPDATE menu_item 
			
			SET @LIST(:menu_item) 
			
		WHERE menu_item_id = :menu_item_id;
	END	



	-- Add new menu

	CREATE PROCEDURE addMenuItem(
		IN menu_item ARRAY,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:menu_item_content_data = @FILTER(:menu_item.menu_item_content, menu_item_content)
		:menu_item_data  = @FILTER(:menu_item, menu_item)

		INSERT INTO menu_item 
		
			( @KEYS(:menu_item_data) )
			
		VALUES ( :menu_item_data );
			
		
		@EACH(:menu_item_content_data) 
			INSERT INTO menu_item_content 
		
				( menu_item_id, @KEYS(:each) )
			
			VALUES ( @result.menu_item, :each );
			
	END

	-- Reorder menu items

	CREATE PROCEDURE updateMenuItems(
		IN menu_items ARRAY,
		OUT insert_id
	)
	BEGIN
		
		:menu_item_data  = @FILTER(:menu_items, menu_item)
		
		@EACH(:menu_item_data) 
			UPDATE menu_item
			
				SET @LIST(:each) 
			
			WHERE menu_item_id = :each.menu_item_id;
		
	END	
	
	-- Delete menu item recursive

	CREATE PROCEDURE deleteMenuItemRecursive(
		IN menu_item_id INT,
		OUT affected_rows,
		OUT affected_rows,
	)
	BEGIN
	
		-- delete menu_item_content
		DELETE FROM menu_item_content WHERE menu_item_id IN (
		WITH RECURSIVE tree AS ( 
				   SELECT menu_item_id, 
					  parent_id
				   FROM menu_item
				   WHERE menu_item_id IN (:menu_item_id)

				   UNION ALL 

				   SELECT p.menu_item_id,
						  p.parent_id 
				   FROM menu_item p
					 JOIN tree t ON t.menu_item_id = p.parent_id
				)
		SELECT menu_item_id FROM tree);
		
		-- delete menu_item
		DELETE FROM menu_item WHERE menu_item_id IN (
		WITH RECURSIVE tree AS ( 
				   SELECT menu_item_id, 
					  parent_id
				   FROM menu_item
				   WHERE menu_item_id IN (:menu_item_id)

				   UNION ALL 

				   SELECT p.menu_item_id,
						  p.parent_id 
				   FROM menu_item p
					 JOIN tree t ON t.menu_item_id = p.parent_id
				)
		SELECT menu_item_id FROM tree);
		
	END
	
	-- Delete menu item

	CREATE PROCEDURE deleteMenuItem(
		IN menu_item_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		-- non CTE for older mysql versions, does not delete grand child menu items
		DELETE FROM menu_item_content WHERE menu_item_id IN (SELECT menu_item_id FROM menu_item WHERE parent_id = :menu_item_id);
		DELETE FROM menu_item_content WHERE menu_item_id IN (:menu_item_id);

		DELETE FROM menu_item WHERE menu_item_id IN (SELECT menu_item_id FROM (SELECT menu_item_id FROM menu_item) as mi WHERE parent_id = :menu_item_id);
		DELETE FROM menu_item WHERE menu_item_id IN (:menu_item_id);
		
	END
