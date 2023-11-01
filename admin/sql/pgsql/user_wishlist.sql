-- Wishlist

	-- get all user wishlist products 

	CREATE PROCEDURE getAll(
		-- variables
		IN user_id INT,
		IN language_id INT,

		-- pagination
		IN start INT,
		IN limit INT,

		-- return
		OUT fetch_all, -- orders
		OUT fetch_one  -- count
	)
	BEGIN

		SELECT *
		
			FROM user_wishlist
			INNER JOIN product_content pc ON pc.product_id = user_wishlist.product_id AND pc.language_id = :language_id
			
		WHERE user_wishlist.user_id = :user_id
            

		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(user_id, user_wishlist) -- this takes previous query removes limit and replaces select columns with parameter user_wishlist_id
			
		) as count;	 
	END
	

	-- get user wishlist

	CREATE PROCEDURE get(
		IN user_id INT,
		IN product_id INT,
		OUT fetch_row,
	)
	BEGIN

		SELECT * 
			FROM user_wishlist AS _
			INNER JOIN product_content pc ON pc.product_id = _.product_id AND _.language_id = :language_id
		WHERE _.user_id = :user_id AND _.product_id = :product_id;

        LIMIT 1; 		
          
	END

	-- Add new product to wishlist

	CREATE PROCEDURE add(
		IN user_wishlist ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:user_wishlist, user_wishlist);
		
		INSERT INTO user_wishlist 
			
			( @KEYS(:user_wishlist) )
			
	  	VALUES ( :user_wishlist )
        
	END

	-- Delete user wishlist

	CREATE PROCEDURE delete(
		IN product_id ARRAY,
		IN user_id INT,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM user_wishlist WHERE user_id = :user_id
            
		-- product
		
		@IF isset(:product_id)
		THEN 
			AND user_wishlist.product_id IN (:product_id)
		END @IF	        
		;
            
	END
