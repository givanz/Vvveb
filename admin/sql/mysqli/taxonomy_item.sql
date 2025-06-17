-- Taxonomy items

	-- get all taxonomy items
	
	PROCEDURE getAll(
		IN language_id INT,
		IN site_id INT,
		IN parent_id INT,
		IN start INT,
		IN limit INT,

		-- filter
		IN post_type CHAR,
		IN type CHAR,

		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- taxonomy_item
		SELECT * FROM taxonomy_item 
			LEFT JOIN taxonomy_to_site t2s ON (taxonomy_item.taxonomy_item_id = t2s.taxonomy_item_id AND site_id = :site_id) 
			LEFT JOIN taxonomy_item_content t2c ON (taxonomy_item.taxonomy_item_id = t2c.taxonomy_item_id AND t2c.language_id = :language_id ) 
		WHERE 1 = 1

		@IF isset(:post_type) AND isset(:type)
		THEN 
			AND taxonomy_id = (SELECT taxonomy_id FROM taxonomy WHERE post_type = :post_type AND type = :type LIMIT 1)
		END @IF			
		
		@IF isset(:parent_id)
		THEN 
			AND parent_id = :parent_id
		END @IF		
		
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(taxonomy_item.taxonomy_item_id, taxonomy_item) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get taxonomy_item

	PROCEDURE get(
		IN taxonomy_item_id INT,
		IN language_id INT,
		IN slug CHAR,
		OUT fetch_row, 
	)
	BEGIN
		-- taxonomy_item
		SELECT * FROM taxonomy_item as _ 
			LEFT JOIN taxonomy_item_content t2c ON (_.taxonomy_item_id = t2c.taxonomy_item_id AND t2c.language_id = :language_id ) 
		WHERE  1 = 1

		@IF isset(:taxonomy_item_id)
		THEN 
		
			AND _.taxonomy_item_id = :taxonomy_item_id

		END @IF			
		
		@IF isset(:slug)
		THEN 
		
			AND t2c.slug = :slug

		END @IF
		
		LIMIT 1;

	END
	
	-- add taxonomy_item

	PROCEDURE add(
		IN taxonomy_item ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:taxonomy_item_data  = @FILTER(:taxonomy_item, taxonomy_item)
		
		
		INSERT INTO taxonomy_item 
			
			( @KEYS(:taxonomy_item_data) )
			
	  	VALUES ( :taxonomy_item_data );

	END
	
	-- edit taxonomy_item
	CREATE PROCEDURE edit(
		IN taxonomy_item ARRAY,
		IN taxonomy_item_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:taxonomy_item, taxonomy_item)

		UPDATE taxonomy_item
			
			SET @LIST(:taxonomy_item) 
			
		WHERE taxonomy_item_id = :taxonomy_item_id


	END
	
