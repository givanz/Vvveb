-- Subscriptions

	-- get all product_subscription

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN product_id INT,
		IN subscription_plan_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (subscription_plan_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- product_subscription
		SELECT
			   @IF isset(:product_id)
			   THEN		
				   sp.*, 
				   spc.name, 
				   spc.content, 
			   END @IF	
			   product_subscription.product_id,
			   product_subscription.user_group_id,
			   product_subscription.price, 
			   product_subscription.trial_price
			   
			FROM product_subscription
			
			-- product_id
			@IF isset(:product_id)
			THEN		
				LEFT JOIN subscription_plan sp ON sp.subscription_plan_id = product_subscription.subscription_plan_id
				LEFT JOIN subscription_plan_content spc ON spc.subscription_plan_id = sp.subscription_plan_id AND spc.language_id = :language_id 
			END @IF	
		
		WHERE 1 = 1
		
		-- product_id
		@IF isset(:product_id)
		THEN		
			AND product_subscription.product_id = :product_id
		END @IF			
		
		-- subscription_plan_id
		@IF isset(:subscription_plan_id)
		THEN		
			AND product_subscription.subscription_plan_id = :subscription_plan_id
		END @IF	
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND product_subscription.subscription_plan_id > :cursor
		
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
			
			@SQL_COUNT(product_subscription.subscription_plan_id, product_subscription) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get product_subscription

	CREATE PROCEDURE get(
		IN product_subscription_id INT,
		IN product_id INT,
		IN subscription_plan_id INT,
		OUT fetch_row
	)
	BEGIN
		-- product_subscription
		SELECT *
			FROM product_subscription as _
		WHERE 1 = 1

		@IF isset(:product_id)
		THEN		
			AND _.product_id = :product_id
		END @IF	

		@IF isset(:subscription_plan_id)
		THEN		
			AND _.subscription_plan_id = :subscription_plan_id
		END @IF	

		@IF isset(:product_subscription_id)
		THEN		
			AND _.product_subscription_id = :product_subscription_id
		END @IF	

		LIMIT 1;
	END
	
	-- add product_subscription

	CREATE PROCEDURE add(
		IN product_subscription ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_subscription_data  = @FILTER(:product_subscription, product_subscription)
		
		
		INSERT INTO product_subscription 
			
			( @KEYS(:product_subscription_data) )
			
	  	VALUES ( :product_subscription_data );

	END
	
	-- edit product_subscription
	
	CREATE PROCEDURE edit(
		IN product_subscription_id INT,
		IN product_subscription ROW,
		IN product_id INT,
		IN subscription_plan_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:product_subscription, product_subscription)

		UPDATE product_subscription
			
			SET @LIST(:product_subscription) 
			
		WHERE 1 = 1

		@IF isset(:product_id)
		THEN		
			AND product_id = :product_id
		END @IF	

		@IF isset(:subscription_plan_id)
		THEN		
			AND subscription_plan_id = :subscription_plan_id
		END @IF	

		@IF isset(:product_subscription_id)
		THEN		
			AND product_subscription_id = :product_subscription_id
		END @IF;	
	END
	
	
	-- delete product_subscription

	CREATE PROCEDURE delete(
		IN product_subscription_id ARRAY,
		IN product_id ARRAY,
		IN subscription_plan_id ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- user_group
		DELETE FROM product_subscription WHERE 1 = 1
		
		@IF isset(:product_id)
		THEN		
			AND product_id IN (:product_id) AND subscription_plan_id IN (:subscription_plan_id)
		END @IF	

		@IF isset(:product_subscription_id)
		THEN		
			AND product_subscription_id IN (:product_subscription_id)
		END @IF;	

	END
