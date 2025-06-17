-- product_variant

	-- get all product_variant

	CREATE PROCEDURE getAll(
		IN product_id INT,
		IN min_stock_quantity INT,
		IN product_variant_id ARRAY,
		
		-- pagination
		IN start INT,
		IN limit INT,
		IN order_by CHAR,
		IN direction CHAR,
		
		OUT fetch_all,
	)
	BEGIN

		SELECT *, options as array_key
			FROM product_variant
		WHERE 1 = 1
		
		@IF !empty(:product_id) 
		THEN 
			AND product_id = :product_id
		END @IF	
		
		@IF !empty(:min_stock_quantity) 
		THEN 
			AND stock_quantity > :min_stock_quantity
		END @IF	
		
		@IF !empty(:product_variant_id) 
		THEN 
			AND product_variant_id IN (:product_variant_id)
		END @IF	


		-- ORDER BY parameters can't be binded, because they are added to the query directly they must be properly sanitized by only allowing a predefined set of values
		@IF isset(:order_by)
		THEN
			ORDER BY product_variant.$order_by $direction		
		@ELSE
			ORDER BY product_variant.product_variant_id ASC
		END @IF
		

		@IF isset(:limit)
		THEN
			@SQL_LIMIT(:start, :limit)
		END @IF;		
		
	END

	-- get one product_variant

	CREATE PROCEDURE get(
		IN product_id INT,
		IN product_variant_id INT,
		
		OUT fetch_row,
	)
	BEGIN

		SELECT *
			FROM product_varian
		WHERE product_variant_id = :product_variant_id  LIMIT 1;
		
	END
    
	CREATE PROCEDURE add(
		IN product_id INT,
		IN product_variant ARRAY,
		
		OUT insert_id
	)
	BEGIN
			-- allow only table fields and set defaults for missing values
		:product_variant  = @FILTER(:product_variant, product_variant)

        INSERT INTO product_variant
            ( @KEYS(:product_variant) )
        
        VALUES (:product_variant );
		
	END

	CREATE PROCEDURE edit(
		IN product_variant ARRAY,
		IN product_variant_id INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		:product_variant  = @FILTER(:product_variant, product_variant)

		UPDATE product_variant 
			
			SET @LIST(:product_variant) 
			
		WHERE product_variant_id = :product_variant_id
	 
	END

	CREATE PROCEDURE delete(
		IN product_id INT,
		IN product_variant_id ARRAY,
		
		OUT affected_rows
	)
	BEGIN

        DELETE FROM 
			product_variant 
		WHERE 1 = 1

		@IF !empty(:product_variant_id) 
		THEN 
			AND product_variant_id IN (:product_variant_id)
		END @IF	

		@IF !empty(:product_id) 
		THEN 
			AND product_id = :product_id
		END @IF	
		;		
		
	END    
