-- Subscription plans

	-- get all subscription plan

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- subscription_plan
		SELECT subscription_plan.*,subscription_plan_content.name
			FROM subscription_plan AS subscription_plan 
			INNER JOIN subscription_plan_content ON subscription_plan_content.subscription_plan_id = subscription_plan.subscription_plan_id 
												 AND subscription_plan_content.language_id = :language_id
		WHERE 1 = 1
			
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(subscription_plan.subscription_plan_id, subscription_plan) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get subscription plan

	PROCEDURE get(
		IN subscription_plan_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- subscription plan
		SELECT _.*,subscription_plan_content.name
			FROM subscription_plan as _ 
			INNER JOIN subscription_plan_content ON subscription_plan_content.subscription_plan_id = _.subscription_plan_id 
												AND subscription_plan_content.language_id = :language_id			
			
			WHERE _.subscription_plan_id = :subscription_plan_id;
	END
	
	-- add subscription plan

	PROCEDURE add(
		IN subscription_plan ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:subscription_plan_data  = @FILTER(:subscription_plan, subscription_plan);
		
		
		INSERT INTO subscription_plan 
			
			( @KEYS(:subscription_plan_data) )
			
	  	VALUES ( :subscription_plan_data );

	END
	
	-- edit subscription plan
	
	CREATE PROCEDURE edit(
		IN subscription_plan ARRAY,
		IN subscription_plan_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:subscription_plan, subscription_plan);

		UPDATE subscription_plan
			
			SET @LIST(:subscription_plan) 
			
		WHERE subscription_plan_id = :subscription_plan_id


	END
	
	
	-- delete subscriptionplan

	PROCEDURE delete(
		IN subscription_plan_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- user_group
		DELETE FROM subscription_plan WHERE subscription_plan_id IN (:subscription_plan_id);
	END
