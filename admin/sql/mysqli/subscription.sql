-- Subscriptions

	-- get all subscription

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (subscription_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- subscription
		SELECT subscription.*, subscription_status.name AS status_name,
		       subscription_plan_content.name AS plan_name,
		       product_content.name AS product_name
			FROM subscription
			LEFT JOIN subscription_status ON subscription_status.subscription_status_id = subscription.subscription_status_id
				AND subscription_status.language_id = :language_id
			LEFT JOIN subscription_plan_content ON subscription_plan_content.subscription_plan_id = subscription.subscription_plan_id
				AND subscription_plan_content.language_id = :language_id
			LEFT JOIN product_content ON product_content.product_id = subscription.product_id
				AND product_content.language_id = :language_id
			WHERE 1 = 1

			@IF isset(:user_id)
			THEN
				AND subscription.user_id = :user_id
			END @IF

			-- cursor pagination

			@IF isset(:cursor)

			THEN

				AND subscription.subscription_id > :cursor

			END @IF

			-- limit

			@IF isset(:limit)
			THEN		
				LIMIT :limit OFFSET :start
			END @IF;
			
			SELECT count(*) FROM (
				
				@SQL_COUNT(subscription.subscription_id, subscription) -- this takes previous query removes limit and replaces select columns with parameter product_id
				
			) as count;		
				
	END	
	
	-- get subscription

	CREATE PROCEDURE get(
		IN subscription_id INT,
		IN language_id INT,
		OUT fetch_row
	)
	BEGIN
		-- subscription
		SELECT _.*, subscription_status.name AS status_name,
		       subscription_plan_content.name AS plan_name,
		       product_content.name AS product_name,
		       product_content.slug AS product_slug
			FROM subscription as _
			LEFT JOIN subscription_status ON subscription_status.subscription_status_id = _.subscription_status_id
				AND subscription_status.language_id = :language_id
			LEFT JOIN subscription_plan_content ON subscription_plan_content.subscription_plan_id = _.subscription_plan_id
				AND subscription_plan_content.language_id = :language_id
			LEFT JOIN product_content ON product_content.product_id = _.product_id
				AND product_content.language_id = :language_id
			WHERE _.subscription_id = :subscription_id;
	END
	
	-- add subscription

	CREATE PROCEDURE add(
		IN subscription ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:subscription_data  = @FILTER(:subscription, subscription)
		
		
		INSERT INTO subscription 
			
			( @KEYS(:subscription_data) )
			
	  	VALUES ( :subscription_data );

	END
	
	-- edit subscription
	
	CREATE PROCEDURE edit(
		IN subscription ROW,
		IN subscription_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:subscription, subscription)

		UPDATE subscription
			
			SET @LIST(:subscription) 
			
		WHERE subscription_id = :subscription_id


	END
	
	
	-- delete subscription

	CREATE PROCEDURE delete(
		IN subscription_id ARRAY,
		OUT affected_rows 
	)
	BEGIN
		-- user_group
		DELETE FROM subscription WHERE subscription_id IN (:subscription_id);
	END
