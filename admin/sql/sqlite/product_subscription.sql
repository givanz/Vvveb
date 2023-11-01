-- Subscriptions

	-- get all product_subscription

	PROCEDURE getAll(
		IN language_id INT,
		IN product_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- product_subscription
		SELECT
			   sp.*, 
			   spc.name, 
			   product_subscription.product_id,
			   product_subscription.user_group_id,
			   product_subscription.price, 
			   product_subscription.trial_price
			   
			FROM product_subscription AS product_subscription
			
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
		
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(product_subscription.subscription_plan_id, product_subscription) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get product_subscription

	PROCEDURE get(
		IN product_subscription_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- product_subscription
		SELECT *
			FROM product_subscription as _ WHERE product_subscription_id = :product_subscription_id;
	END
	
	-- add product_subscription

	PROCEDURE add(
		IN product_subscription ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_subscription_data  = @FILTER(:product_subscription, product_subscription);
		
		
		INSERT INTO product_subscription 
			
			( @KEYS(:product_subscription_data) )
			
	  	VALUES ( :product_subscription_data );

	END
	
	-- edit product_subscription
	
	CREATE PROCEDURE edit(
		IN product_subscription ARRAY,
		IN product_subscription_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:product_subscription, product_subscription);

		UPDATE product_subscription
			
			SET @LIST(:product_subscription) 
			
		WHERE product_subscription_id = :product_subscription_id


	END
	
	
	-- delete product_subscription

	PROCEDURE delete(
		IN product_subscription_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- user_group
		DELETE FROM product_subscription WHERE product_subscription_id IN (:product_subscription_id);
	END
