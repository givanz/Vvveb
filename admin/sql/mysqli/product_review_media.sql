-- Product reviews

	
	CREATE PROCEDURE getAll(
		-- variables
		IN 	product_id INT,
        IN 	product_review_id INT,
        IN 	status INT,

		-- pagination
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (product_review_media_id) for cursor pagination

		-- return
		OUT fetch_all, -- orders
		OUT fetch_one  -- count
	)
	BEGIN

		SELECT *
            FROM product_review_media
			INNER JOIN product_review ON product_review.product_review_id = product_review_media.product_review_id
		
			WHERE 1 = 1
            
            -- post
            @IF isset(:product_id)
			THEN 
				AND product_review_media.product_id  = :product_id
        	END @IF	            
            

            -- product_review
            @IF isset(:product_review_id)
			THEN 
				AND product_review_media.product_review_id  = :product_review_id
        	END @IF	                 
			
	   -- status
            @IF isset(:status)
			THEN 
				AND product_review.status  = :status
        	END @IF	              
            

		-- cursor pagination
            

		@IF isset(:cursor)
            

		THEN
            

		    AND product_review_media.product_review_media_id > :cursor
            

		END @IF
            

		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(product_review_media.product_review_media_id, product_review_media) -- this takes previous query removes limit and replaces select columns with parameter product_review_media_id
			
		) as count;
		
		
	END

	-- Get product review
	
	CREATE PROCEDURE get(
		IN product_review_media_id INT,
		OUT fetch_row
	)
	BEGIN
		-- review
		SELECT *
			FROM product_review_media as _ -- (underscore) _ means that data will be kept in main array
		INNER JOIN product_review on product_review.product_review_id = _.product_review_id
		WHERE _.product_review_media_id = :product_review_media_id LIMIT 1;

	END
	
	-- Add new product review

	CREATE PROCEDURE add(
		IN product_review_media ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:product_review_media, product_review_media)
		
		INSERT INTO product_review_media 
			
			( @KEYS(:product_review_media) )
			
	  	VALUES ( :product_review_media )
        
	END

	-- Edit product review

	CREATE PROCEDURE edit(
		IN product_review_media ROW,
		IN  product_review_media_id INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:product_review_media, product_review_media)

		UPDATE product_review_media 
			
			SET  @LIST(:product_review_media) 
			
		WHERE product_review_media_id = :product_review_media_id
	 
	END
	
	-- Delete product review

	CREATE PROCEDURE delete(
		IN  product_review_media_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM product_review_media WHERE product_review_media_id IN (:product_review_media_id)
	 
	END
