-- Fields

	-- get all fields

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN taxonomy_item_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (field_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- field
		SELECT taxonomy_item_field_value.*, field.settings, field.field_id as array_key
			FROM taxonomy_item_field_value
			LEFT JOIN field ON (field.field_id = taxonomy_item_field_value.field_id)

		WHERE 1 = 1
			
		@IF !empty(:field_group_id) 
		THEN			
			AND field.field_group_id = :field_group_id
		END @IF
		
		@IF !empty(:language_id) 
		THEN			
			AND taxonomy_item_field_value.language_id = :language_id
		END @IF
		
		@IF !empty(:taxonomy_item_id) 
		THEN			
			AND taxonomy_item_field_value.taxonomy_item_id = :taxonomy_item_id
		END @IF
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND field.field_id > :cursor
		
		END @IF
		
		ORDER BY field.sort_order
		
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(field.field_id, field) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get field

	CREATE PROCEDURE get(
		IN field_id INT,
		IN taxonomy_item_id INT,
		IN language_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- field
		SELECT *
			FROM taxonomy_item_field_value as _ 
			INNER JOIN field ON field.field_id = _.field_id
		WHERE _.field_id = :field_id

		@IF !empty(:language_id) 
		THEN			
			AND _.language_id = :language_id
		END @IF
		
		@IF !empty(:field_id) 
		THEN			
			AND _.field_id = :field_id
		END @IF
		
		@IF !empty(:taxonomy_item_id) 
		THEN			
			AND _.taxonomy_item_id = :taxonomy_item_id
		END @IF
		
		;
	END
	
	-- add field

	CREATE PROCEDURE add(
		IN taxonomy_item_field_value ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:taxonomy_item_field_value  = @FILTER(:taxonomy_item_field_value, taxonomy_item_field_value)
		
		INSERT INTO taxonomy_item_field_value 
			
			( @KEYS(:taxonomy_item_field_value) )
			
	  	VALUES ( :taxonomy_item_field_value);

	END
	
	-- edit field
	CREATE PROCEDURE edit(
		IN taxonomy_item_field_value ROW,
		IN taxonomy_item_id INT,
		IN field_id INT,
		IN language_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:taxonomy_item_field_value  = @FILTER(:taxonomy_item_field_value, taxonomy_item_field_value)

		UPDATE taxonomy_item_field_value 
			
			SET @LIST(:taxonomy_item_field_value) 
			
		WHERE field_id = :field_id AND taxonomy_item_id = :taxonomy_item_id AND language_id = :language_id;


	END
	
	-- delete field

	CREATE PROCEDURE delete(
		IN taxonomy_item_id INT,
		IN field_id INT,
		IN language_id INT,
		OUT affected_rows 
	)
	BEGIN
		-- taxonomy_item_field_value_id
		DELETE FROM taxonomy_item_field_value WHERE 1 = 1
		
		@IF !empty(:language_id) 
		THEN			
			AND taxonomy_item_field_value.language_id IN (:language_id)
		END @IF
		
		@IF !empty(:field_id) 
		THEN			
			AND taxonomy_item_field_value.field_id IN (:field_id)
		END @IF
		
		@IF !empty(:taxonomy_item_id) 
		THEN			
			AND taxonomy_item_field_value.taxonomy_item_id IN (:taxonomy_item_id)
		END @IF
	END
