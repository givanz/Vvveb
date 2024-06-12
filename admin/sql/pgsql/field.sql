-- Taxonomy

	-- get all taxonomies

	CREATE PROCEDURE getAll(
		IN field_item_id INT,
		
		-- pagination
		IN start INT,
		IN limit INT,
		
		-- filter
		IN post_type CHAR,

		OUT fetch_all, 
		OUT fetch_one 
	)
	BEGIN
		-- field_item
		SELECT *, field_id as array_key 
			FROM field as field 
				-- LEFT JOIN field_to_site t2s ON (field_item.field_item_id = t2s.field_item_id) 
			
			WHERE 1 = 1
			
			@IF isset(:post_type)
			THEN 
			
				AND post_type = :post_type
				
			END @IF			
			
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;
		
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(field.field_id, field) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END
	

