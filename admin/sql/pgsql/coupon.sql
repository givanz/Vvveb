-- Coupons

	-- get all coupons

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- coupon
		SELECT *
			FROM coupon WHERE 1 = 1
			
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(coupon.coupon_id, coupon) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get coupon

	PROCEDURE get(
		IN coupon_id INT,
		IN code CHAR,
		IN free_shipping INT,
		IN status INT,
		OUT fetch_row, 
	)
	BEGIN
		-- coupon
		SELECT *
			FROM coupon as _ 
		WHERE 1 = 1 
			
		@IF !empty(:code) 
		THEN			
			AND code = :code
		END @IF				
		
		@IF !empty(:free_shipping) 
		THEN			
			AND free_shipping = :free_shipping
		END @IF				
		
		@IF !empty(:coupon_id) 
		THEN			
			AND coupon_id = :coupon_id
		END @IF		

		@IF isset(:status) AND :status != "" 
		THEN			
			AND status = :status
		END @IF
		;			
			
	END
	
	-- add coupon

	PROCEDURE add(
		IN coupon ARRAY,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:coupon_data  = @FILTER(:coupon, coupon)
		
		
		INSERT INTO coupon 
			
			( @KEYS(:coupon_data) )
			
	  	VALUES ( :coupon_data ) RETURNING coupon_id;

	END
	
	-- edit coupon
	
	CREATE PROCEDURE edit(
		IN coupon ARRAY,
		IN coupon_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:coupon, coupon)

		UPDATE coupon
			
			SET @LIST(:coupon) 
			
		WHERE coupon_id = :coupon_id


	END

	-- delete coupon

	PROCEDURE delete(
		IN coupon_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- coupon
		DELETE FROM coupon WHERE coupon_id IN(:coupon_id);
	END
	

	-- get coupon categories

	PROCEDURE getTaxonomies(
		IN coupon_id INT,
		IN language_id INT,
		OUT fetch_all
	)
	BEGIN
		-- coupon
		SELECT *
			FROM coupon_taxonomy 
			INNER JOIN taxonomy_item_content tic ON tic.taxonomy_item_id = coupon_taxonomy.taxonomy_item_id AND tic.language_id = :language_id
		WHERE coupon_id = :coupon_id;
	END
	
	-- get coupon products

	PROCEDURE getProducts(
		IN coupon_id INT,
		IN language_id INT,
		OUT fetch_all
	)
	BEGIN
		-- coupon
		SELECT *
			FROM coupon_product 
			INNER JOIN product_content pc ON pc.product_id = coupon_product.product_id
		WHERE coupon_id = :coupon_id;
	END


	-- set coupon taxonomies

	PROCEDURE setTaxonomies(
		IN coupon_taxonomy ARRAY,
		IN coupon_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM coupon_taxonomy WHERE coupon_id = :coupon_id;
		
		@EACH(:coupon_taxonomy) 
			INSERT INTO coupon_taxonomy 
		
				( taxonomy_item_id, coupon_id)
			
			VALUES ( :each, :coupon_id );

	END				
	
	-- set coupon products

	PROCEDURE setProducts(
		IN coupon_product ARRAY,
		IN coupon_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM coupon_product WHERE coupon_id = :coupon_id;
		
		@EACH(:coupon_product) 
			INSERT INTO coupon_product 
		
				( product_id, coupon_id)
			
			VALUES ( :each, :coupon_id );

	END
