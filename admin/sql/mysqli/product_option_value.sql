-- Options

	-- get all product_option_values

	PROCEDURE getAll(
		IN language_id INT,
		IN option_id INT,
		IN product_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- product_option_value
		SELECT product_option_value.*, option_value.image, ovc.name, product_option_value.product_option_value_id as array_key
			FROM product_option_value AS product_option_value
			INNER JOIN option_value ON option_value.option_value_id = product_option_value.option_value_id
			INNER JOIN option_value_content ovc ON ovc.option_value_id = product_option_value.option_value_id AND ovc.language_id = :language_id
			
		WHERE 1 = 1
			
		-- option_id
		@IF isset(:option_id)
		THEN		
			AND product_option_value.option_id = :option_id
		END @IF		
		
		-- product_id
		@IF isset(:product_id)
		THEN		
			AND product_option_value.product_id = :product_id
		END @IF		
		
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(product_option_value.product_option_value_id, product_option_value) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get product_option_value

	PROCEDURE get(
		IN product_option_value_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- product_option_value
		SELECT *
			FROM product_option_value as _ 
			INNER JOIN option_value ON option_value.option_value_id = _.option_value_id
			INNER JOIN option_value_content ovc ON ovc.option_value_id = _.option_value_id AND ovc.language_id = :language_id

		WHERE _.product_option_value_id = :product_option_value_id;
	END
	
	-- add product_option_value

	PROCEDURE add(
		IN product_option_value ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_option_value_data  = @FILTER(:product_option_value, product_option_value);
		
		
		INSERT INTO product_option_value 
			
			( @KEYS(:product_option_value_data) )
			
	  	VALUES ( :product_option_value_data );		
		

	END
	
	-- edit product_option_value
	
	CREATE PROCEDURE edit(
		IN product_option_value ARRAY,
		IN product_option_value_id INT,
		OUT affected_rows
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:product_option_value_data = @FILTER(:product_option_value, product_option_value);

		UPDATE product_option_value
			
			SET @LIST(:product_option_value_data) 
			
		WHERE product_option_value_id = :product_option_value_id;

	END
	
	
	-- delete product_option_value

	PROCEDURE delete(
		IN product_option_value_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- product_option_value
		DELETE FROM product_option_value WHERE product_option_value_id IN (:product_option_value_id);
	END
