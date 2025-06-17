-- Carts

	-- get all carts

	PROCEDURE getAll(
		IN cart_id ARRAY,
		IN user_id CHAR,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- cart
		SELECT *
			FROM cart WHERE 1 = 1
			
		@IF isset(:cart_id) AND :cart_id
		THEN			
			AND cart_id IN (:cart_id)
		END @IF			
		
		@IF isset(:user_id) AND :user_id
		THEN			
			AND user_id IN (:user_id)
		END @IF
			

		ORDER BY status DESC, cart_id

		@IF !empty(:limit) 
		THEN			
			@SQL_LIMIT(:start, :limit)
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(cart.cart_id, cart) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get cart

	PROCEDURE get(
		IN cart_id INT,
		IN user_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- cart
		SELECT *
			FROM cart as _ WHERE 1 = 1
			
			@IF isset(:cart_id) AND :cart_id
			THEN			
				AND cart_id = :cart_id
			END @IF			
			
			@IF isset(:user_id) AND :user_id
			THEN			
				AND user_id = :user_id
			END @IF
			;		
		
	END
	
	-- add cart

	PROCEDURE add(
		IN cart ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:cart_data  = @FILTER(:cart, cart)
		
		
		INSERT INTO cart 
			
			( @KEYS(:cart_data) )
			
	  	VALUES ( :cart_data );

	END
	
	-- edit cart
	
	CREATE PROCEDURE edit(
		IN cart ARRAY,
		IN cart_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:cart, cart)

		UPDATE cart 
			
			SET @LIST(:cart) 
			
		WHERE cart_id = :cart_id


	END	
	
	-- edit cart
	
	CREATE PROCEDURE update(
		IN cart ARRAY,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:cart, cart)

		INSERT INTO cart 
	
			( @KEYS(:cart))
		
		VALUES ( :cart )
				
		ON DUPLICATE KEY 
			UPDATE data = values(data), user_id = values(user_id);

	END
	
	-- delete cart

	PROCEDURE delete(
		IN cart_id ARRAY,
		IN user_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- cart
		DELETE FROM cart WHERE 1 = 1

		@IF isset(:cart_id) AND :cart_id
		THEN			
			AND cart_id IN (:cart_id)
		END @IF			
		
		@IF isset(:user_id) AND :user_id
		THEN			
			AND user_id IN (:user_id)
		END @IF;
	END
