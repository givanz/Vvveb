-- Categories
        
	-- get common code from admin
	import(/admin/category.sql);  

	
	-- get all posts grouped by categories 

	CREATE PROCEDURE getPostsAndCategories(

		-- variables
		IN  language_id INT,
		IN  taxonomy_id INT,
		IN  site_id INT,
		IN  post_id INT,
		IN  search CHAR,
		
		-- pagination
		IN  start INT,
		IN count INT,
		
		-- return array of categories for categories query
		OUT fetch_all,
		-- return categories count for count query
		OUT fetch_one,
	)
	BEGIN

		SELECT  SQL_CALC_FOUND_ROWS *, post.post_id as array_key

		FROM post
		
			LEFT JOIN post_content tc ON (post.post_id = tc.post_id AND tc.language_id = :language_id)  
			LEFT JOIN post_to_site ps ON (post.post_id = ps.post_id)  
			LEFT JOIN post_to_taxonomy_item pt ON (post.post_id = pt.post_id)   

			LEFT JOIN taxonomy_item categories ON (categories.taxonomy_item_id = pt.taxonomy_item_id) 
			LEFT JOIN taxonomy_to_site c2s ON (categories.taxonomy_item_id = c2s.taxonomy_item_id) 
			LEFT JOIN taxonomy_item_content td ON (categories.taxonomy_item_id = td.taxonomy_item_id)  

			WHERE 
			
			tc.language_id = :language_id AND c2s.site_id = :site_id

			@IF isset(:search)
			THEN 
			
				AND tc.name LIKE CONCAT('%',:search,'%')
				
			END @IF				
			
		LIMIT :start, :count;
		
		SELECT FOUND_ROWS() as count;

	END

	-- get one taxonomy_item

	CREATE PROCEDURE getCategory(
		IN  language_id INT,
		IN  site_id INT,

		IN post_id INT,
		IN product_id INT,
		IN post_type CHAR,
		IN type CHAR,

		IN taxonomy_item_id INT,
		IN slug CHAR,
		
		OUT fetch_row
	)
	BEGIN
	
	
		SELECT *,tc.name as name, tc.slug as slug

		FROM taxonomy_item AS _
		
			LEFT JOIN taxonomy_to_site c2s ON (_.taxonomy_item_id = c2s.taxonomy_item_id) 
			LEFT JOIN taxonomy_item_content tc ON (_.taxonomy_item_id = tc.taxonomy_item_id)  

				@IF isset(:post_id) THEN
				
					@IF :type == "tags"
					THEN 
					
						INNER JOIN post_to_taxonomy_item pt ON (_.taxonomy_item_id = pt.taxonomy_item_id AND pt.post_id = :post_id)  
					@ELSE		
					
						LEFT JOIN post_to_taxonomy_item pt ON (_.taxonomy_item_id = pt.taxonomy_item_id AND pt.post_id = :post_id)  
						
					END @IF		
					
				END @IF		
				
				@IF isset(:product_id) THEN
				
					@IF :type == "tags"
					THEN 
					
						INNER JOIN product_to_taxonomy_item pt ON (_.taxonomy_item_id = pt.taxonomy_item_id AND pt.product_id = :product_id)  
					@ELSE		
					
						LEFT JOIN product_to_taxonomy_item pt ON (_.taxonomy_item_id = pt.taxonomy_item_id AND pt.product_id = :product_id)  
						
					END @IF	
					
				END @IF	
				
				@IF isset(:type)
				THEN 
					INNER JOIN taxonomy t ON (
						_.taxonomy_id = t.taxonomy_id AND t.type = :type

						@IF isset(:post_type)
						THEN 
						
							AND t.post_type = :post_type 
							
						END @IF							
					)   
				END @IF	
				
			WHERE 
			
			tc.language_id = :language_id AND c2s.site_id = :site_id
			

            @IF isset(:slug)
			THEN 
				AND tc.slug = :slug 
        	END @IF			

            @IF isset(:taxonomy_item_id)
			THEN 
                AND _.taxonomy_item_id = :taxonomy_item_id
        	END @IF		

			@IF isset(:post_id)
			THEN 
				AND pt.post_id = :post_id 
			END @IF					
			
			@IF isset(:product_id)
			THEN 
				AND pt.product_id = :product_id 
        	END @IF		
        
		LIMIT 1
	
	END
	
