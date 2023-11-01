-- Product reviews

	
	CREATE PROCEDURE getAll(
		-- variables
		IN  language_id INT,
		IN  site_id INT,
		IN 	product_id INT,
        IN 	user_id INT,
        IN 	status INT,

		-- pagination
		IN start INT,
		IN limit INT,

		-- return
		OUT fetch_all, -- orders
		OUT fetch_one  -- count
	)
	BEGIN

		SELECT *
            FROM product_review AS product_review
		
			WHERE 1 = 1
            
            -- post
            @IF isset(:product_id)
			THEN 
				AND product_review.product_id  = :product_id
        	END @IF	            
            
	   -- post slug
            @IF isset(:slug)
		THEN 
			AND product_review.product_id  = (SELECT product_id FROM product_content WHERE slug = :slug LIMIT 1) 
	      END @IF

            -- user
            @IF isset(:user_id)
			THEN 
				AND product_review.user_id  = :user_id
        	END @IF	              
            
			-- status
            @IF isset(:status)
			THEN 
				AND product_review.status  = :status
        	END @IF	            

		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(product_review.product_review_id, product_review) -- this takes previous query removes limit and replaces select columns with parameter product_review_id
			
		) as count;
		
		
	END

	-- Get product review

	CREATE PROCEDURE get(
		IN product_review_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- review
		SELECT *
			FROM product_review as _ -- (underscore) _ means that data will be kept in main array
		WHERE product_review_id = :product_review_id LIMIT 1;

	END
	
	-- Add new product review

	CREATE PROCEDURE add(
		IN product_review ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:product_review, product_review);
		
		INSERT INTO product_review 
			
			( @KEYS(:product_review) )
			
	  	VALUES ( :product_review )
        
	END

	-- Edit product review

	CREATE PROCEDURE edit(
		IN product_review ARRAY,
		IN  id_product_review INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:product_review, product_review);

		UPDATE product_review 
			
			SET  @LIST(:product_review) 
			
		WHERE product_review_id = :product_review_id
	 
	END
	
	-- Delete product review

	CREATE PROCEDURE delete(
		IN  product_review_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM product_review WHERE product_review_id IN (:product_review_id)
	 
	END
