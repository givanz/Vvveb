-- Product options

	-- get all product options

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN product_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (product_option_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- product_option
		SELECT product_option.*, oc.name, `option`.type, product_option.product_option_id as array_key
			FROM product_option
			INNER JOIN `option` ON `option`.option_id = product_option.option_id
			INNER JOIN option_content oc ON oc.option_id = product_option.option_id AND oc.language_id = :language_id
		
		WHERE 1 = 1
			
		-- product_id
		@IF isset(:product_id)
		THEN		
			AND product_option.product_id = :product_id
		END @IF		
			
		-- cursor pagination
			
		@IF isset(:cursor)
			
		THEN
			
			AND product_option.product_option_id > :cursor
			
		END @IF
			
		-- limit
		@IF isset(:limit)
		THEN
		    LIMIT :limit

		    @IF isset(:start)
		    THEN
			OFFSET :start
		    END @IF
			
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(product_option.product_option_id, product_option) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get product option

	CREATE PROCEDURE get(
		IN product_option_id INT,
		IN language_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- product_option
		SELECT *
			FROM product_option as _ 
			INNER JOIN `option` ON `option`.option_id = _.option_id
			INNER JOIN option_content oc ON oc.option_id = _.option_id AND oc.language_id = :language_id
		
		WHERE product_option_id = :product_option_id;
	END
	
	-- add product_option

	CREATE PROCEDURE add(
		IN product_option ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_option_data  = @FILTER(:product_option, product_option)
		
		
		INSERT INTO product_option 
			
			( @KEYS(:product_option_data) )
			
	  	VALUES ( :product_option_data );

	END
	
	-- edit product option

	CREATE PROCEDURE edit(
		IN product_option ROW,
		IN product_option_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:product_option, product_option)

		UPDATE product_option
			
			SET @LIST(:product_option) 
			
		WHERE product_option_id = :product_option_id


	END
	
	
	-- delete product option

	CREATE PROCEDURE delete(
		IN product_option_id ARRAY,
		OUT affected_rows 
	)
	BEGIN
		DELETE FROM product_option WHERE product_option_id IN (:product_option_id);
	END
