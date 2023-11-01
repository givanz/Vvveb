-- Taxonomy

	-- get all taxonomies

	CREATE PROCEDURE getAll(
		IN taxonomy_item_id INT,
		
		-- pagination
		IN start INT,
		IN limit INT,
		
		-- filter
		IN post_type CHAR,
		IN type CHAR,

		OUT fetch_all, 
		OUT fetch_one 
	)
	BEGIN
		-- taxonomy_item
		SELECT *, taxonomy_id as array_key 
			FROM taxonomy as taxonomy 
				-- LEFT JOIN taxonomy_to_site t2s ON (taxonomy_item.taxonomy_item_id = t2s.taxonomy_item_id) 
			
			WHERE 1 = 1
			
			@IF isset(:post_type)
			THEN 
			
				AND post_type = :post_type
			END @IF				
							
			@IF isset(:type)
			THEN 
			
				AND type = :type
				
			END @IF			
			
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;
		
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(taxonomy.taxonomy_id, taxonomy) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END
	

	-- get taxonomy

	PROCEDURE get(
		IN taxonomy_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- taxonomy
		SELECT *
			FROM taxonomy as _ WHERE taxonomy_id = :taxonomy_id;
	END
	
	-- add taxonomy

	PROCEDURE add(
		IN taxonomy ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:taxonomy_data  = @FILTER(:taxonomy, taxonomy);
		
		
		INSERT INTO taxonomy 
			
			( @KEYS(:taxonomy_data) )
			
	  	VALUES ( :taxonomy_data);

	END
	
	-- edit taxonomy
	CREATE PROCEDURE edit(
		IN taxonomy ARRAY,
		IN taxonomy_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:taxonomy, taxonomy);

		UPDATE taxonomy
			
			SET @LIST(:taxonomy) 
			
		WHERE taxonomy_id = :taxonomy_id


	END
	
	-- delete taxonomy

	PROCEDURE delete(
		IN taxonomy_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- taxonomy
		DELETE FROM taxonomy WHERE taxonomy_id IN (:taxonomy_id);
	END
