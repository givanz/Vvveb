-- Product questions

	CREATE PROCEDURE getAll(
		-- variables
		IN language_id INT,
		IN site_id INT,
		IN product_id INT,
		IN slug CHAR,
		IN user_id INT,
		IN admin_id INT,
		IN status INT,

		-- pagination
		IN start INT,
		IN limit INT,

		-- return
		OUT fetch_all, -- orders
		OUT fetch_one  -- count
	)
	BEGIN

            SELECT user.username, user.email, user.first_name, user.last_name, user.display_name, user.avatar, user.bio,  user.subscribe, product_question.*,user.user_id as user_id
            FROM product_question
	    LEFT JOIN user on user.user_id = product_question.user_id
			@IF isset(:admin_id) AND :admin_id
			THEN 
				LEFT JOIN product ON (product.product_id = product_question.product_id)
			END @IF			
            WHERE 1 = 1
            
            -- product
            @IF isset(:product_id)
			THEN 
				AND product_question.product_id  = :product_id
        	END @IF	            
            
            -- product slug
            @IF isset(:slug)
            THEN 
				AND product_question.product_id  = (SELECT product_id FROM product_content WHERE slug = :slug LIMIT 1) 
			END @IF
	
            -- user
            @IF isset(:user_id)
            THEN 
            	AND product_question.user_id  = :user_id
            END @IF	              
            

            @IF isset(:admin_id)
            THEN 
            	AND product.admin_id  = :admin_id
            END @IF

            -- status
            @IF isset(:status)
            THEN 
            	AND product_question.status  = :status
            END @IF	            

            @SQL_LIMIT(:start, :limit);
		
            SELECT count(*) FROM (
			
			@SQL_COUNT(product_question.product_question_id, product_question) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
            ) as count;
		
		
	END
	
	-- Get product question
	
	CREATE PROCEDURE get(
		IN product_question_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- question
		SELECT *
			FROM product_question as _ -- (underscore) _ means that data will be kept in main array
		LEFT JOIN user on user.user_id = _.user_id
		WHERE product_question_id = :product_question_id LIMIT 1;

	END
		
	-- Add new product question

	CREATE PROCEDURE add(
		IN product_question ARRAY,
		IN admin_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:product_question, product_question)
		
		INSERT INTO product_question 
			
			( @KEYS(:product_question) )
			
	  	VALUES ( :product_question )
        
	END

	-- Edit product question

	CREATE PROCEDURE edit(
		IN product_question ARRAY,
		IN  id_product_question INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:product_question, product_question)

		UPDATE product_question 
			
			SET  @LIST(:product_question) 
			
		WHERE product_question_id = :product_question_id

		@IF isset(:admin_id)
		THEN
			AND product_review.product_id IN (SELECT product_id FROM product WHERE product.admin_id = :admin_id)
		END @IF;	 
	END
	
	-- Delete product_question

	CREATE PROCEDURE delete(
		IN  product_question_id ARRAY,
		IN admin_id INT,
		OUT affected_rows
	)
	BEGIN

		DELETE product_question FROM product_question

		@IF isset(:admin_id)
		THEN
			INNER JOIN product ON (product.product_id = product_question_id.product_id AND product.admin_id = :admin_id)
		END @IF

		WHERE product_question_id IN (:product_question_id)	 
	END
