-- Fields

	-- get all fields

	PROCEDURE getAll(
		IN language_id INT,
		IN product_id INT,
		IN subtype CHAR,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- field
		SELECT product_field_value.*, field.settings, field.field_id as array_key
			FROM product_field_value
			LEFT JOIN field ON (field.field_id = product_field_value.field_id)
		@IF !empty(:subtype) 
		THEN			
			-- INNER JOIN field_group ON (field.field_group_id = field_group.field_group_id AND field_group.subtype = :subtype)
		END @IF
		
		WHERE 1 = 1
			
		@IF !empty(:field_group_id) 
		THEN			
			AND field.field_group_id = :field_group_id
		END @IF
		
		@IF !empty(:language_id) 
		THEN			
			AND product_field_value.language_id = :language_id
		END @IF
		
		@IF !empty(:product_id) 
		THEN			
			AND product_field_value.product_id = :product_id
		END @IF
		
		ORDER BY field.sort_order
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(field.field_id, field) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get field

	PROCEDURE get(
		IN field_id INT,
		IN product_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- field
		SELECT *
			FROM product_field_value as _ 
			INNER JOIN field ON field_content.field_id = _.field_id
		WHERE _.field_id = :field_id

		@IF !empty(:language_id) 
		THEN			
			AND product_field_value.language_id = :language_id
		END @IF
		
		@IF !empty(:field_id) 
		THEN			
			AND product_field_value.field_id = :field_id
		END @IF
		
		@IF !empty(:product_id) 
		THEN			
			AND product_field_value.product_id = :product_id
		END @IF
		
		;
	END
	
	-- add field

	PROCEDURE add(
		IN product_field_value ARRAY,
		IN language_id INT,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_field_value  = @FILTER(:product_field_value, product_field_value)
		
		INSERT INTO product_field_value 
			
			( @KEYS(:field_data) )
			
	  	VALUES ( :field_data);

	END
	
	-- edit field
	CREATE PROCEDURE edit(
		IN product_field_value ARRAY,
		IN product_field_value_id INT,
		IN product_id INT,
		IN field_id INT,
		IN language_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:product_field_value  = @FILTER(:product_field_value, product_field_value)

		UPDATE product_field_value 
			
			SET @LIST(:product_field_value) 
			
		WHERE field_id = :field_id AND product_id = :product_id AND language_id = :language_id;


	END
	
	-- delete field

	PROCEDURE delete(
		IN product_id INT,
		IN field_id INT,
		IN language_id INT,
		OUT affected_rows, 
	)
	BEGIN
		-- product_field_value_id
		DELETE FROM product_field_value WHERE 
		
		@IF !empty(:language_id) 
		THEN			
			AND product_field_value.language_id IN (:language_id)
		END @IF
		
		@IF !empty(:field_id) 
		THEN			
			AND product_field_value.field_id IN (:field_id)
		END @IF
		
		@IF !empty(:product_id) 
		THEN			
			AND product_field_value.post_id IN (:post_id)
		END @IF
	END
