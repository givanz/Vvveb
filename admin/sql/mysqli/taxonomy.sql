-- Taxonomy

	-- get all taxonomies

	CREATE PROCEDURE getAll(
		
		-- pagination
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (taxonomy_id) for cursor pagination
		
		-- filter
		IN post_type CHAR,
		IN type CHAR,

		OUT fetch_all, 
		OUT fetch_one 
	)
	BEGIN
		-- taxonomy_item
		SELECT *, taxonomy_id as array_key 
			FROM taxonomy 
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
				-- cursor pagination
				@IF isset(:cursor)
				THEN
					AND taxonomy.taxonomy_id > :cursor
				END @IF
				LIMIT :limit OFFSET :start
			END @IF;
		
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(taxonomy.taxonomy_id, taxonomy) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END
	

	-- get taxonomy

	CREATE PROCEDURE get(
		IN taxonomy_id INT,
		IN slug CHAR,
		IN language_id INT,
		OUT fetch_row
	)
	BEGIN
		-- taxonomy
		
	-- taxonomy_item
		SELECT * FROM taxonomy as _ 
			LEFT JOIN taxonomy_content t2c ON (_.taxonomy_id = t2c.taxonomy_id AND t2c.language_id = :language_id ) 
		WHERE  1 = 1

		@IF isset(:taxonomy_id)
		THEN 
		
			AND _.taxonomy_id = :taxonomy_id

		END @IF			
		
		@IF isset(:slug)
		THEN 
		
			AND t2c.slug = :slug

		END @IF
		
		LIMIT 1;

	END
	
	-- add taxonomy

	CREATE PROCEDURE add(
		IN taxonomy ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:taxonomy_data  = @FILTER(:taxonomy, taxonomy)
		
		
		INSERT INTO taxonomy 
			
			( @KEYS(:taxonomy_data) )
			
	  	VALUES ( :taxonomy_data);

	END
	
	-- edit taxonomy
	CREATE PROCEDURE edit(
		IN taxonomy ROW,
		IN taxonomy_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:taxonomy, taxonomy)

		@IF !empty(:taxonomy)
		THEN
		UPDATE taxonomy
			
			SET @LIST(:taxonomy) 
			
		WHERE taxonomy_id = :taxonomy_id
		END @IF


	END
	
	-- delete taxonomy

	CREATE PROCEDURE delete(
		IN taxonomy_id ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- taxonomy
		DELETE FROM taxonomy WHERE taxonomy_id IN (:taxonomy_id);
	END
