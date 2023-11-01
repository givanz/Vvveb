-- Taxonomy items

	-- get all taxonomy items

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- taxonomy_item
		SELECT * FROM taxonomy_item 
			WHERE 1 = 1
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(taxonomy_item.taxonomy_item_id, taxonomy_item) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get taxonomy_item

	PROCEDURE get(
		IN taxonomy_item_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- taxonomy_item
		SELECT * FROM taxonomy_item as _ WHERE taxonomy_item_id = :taxonomy_item_id;
	END
	
	-- add taxonomy_item

	PROCEDURE add(
		IN taxonomy_item ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:taxonomy_item_data  = @FILTER(:taxonomy_item, taxonomy_item);
		
		
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
		@FILTER(:taxonomy_item, taxonomy_item);

		UPDATE taxonomy_item
			
			SET @LIST(:taxonomy_item) 
			
		WHERE taxonomy_item_id = :taxonomy_item_id


	END
	
