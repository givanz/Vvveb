-- Product attributes

	-- get all product attributes

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- product_attribute
		SELECT product_attribute.*
			FROM product_attribute AS product_attribute
		
		WHERE 1 = 1
			
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(product_attribute.product_attribute_id, product_attribute) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get product attribute

	PROCEDURE get(
		IN product_attribute_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- product_attribute
		SELECT *
			FROM product_attribute as _ WHERE product_attribute_id = :product_attribute_id;
	END
	
	-- add product attribute

	PROCEDURE add(
		IN product_attribute ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_attribute_data  = @FILTER(:product_attribute, product_attribute);
		
		
		INSERT INTO product_attribute 
			
			( @KEYS(:product_attribute_data) )
			
	  	VALUES ( :product_attribute_data );

	END
	
	-- edit product_attribute
	
	CREATE PROCEDURE edit(
		IN product_attribute ARRAY,
		IN product_attribute_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:product_attribute, product_attribute);

		UPDATE product_attribute
			
			SET @LIST(:product_attribute) 
			
		WHERE product_attribute_id = :product_attribute_id


	END
	
	
	-- delete product attribute

	PROCEDURE delete(
		IN product_attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		DELETE FROM product_attribute_content WHERE product_attribute_id IN (:product_attribute_id);
		DELETE FROM product_attribute WHERE product_attribute_id IN (:product_attribute_id);
	END
