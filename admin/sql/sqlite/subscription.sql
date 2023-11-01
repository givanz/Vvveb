-- Subscriptions

	-- get all subscription

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- subscription
		SELECT *
			FROM subscription AS subscription WHERE 1 = 1
			
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(subscription.subscription_id, subscription) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get subscription

	PROCEDURE get(
		IN subscription_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- subscription
		SELECT *
			FROM subscription as _ WHERE subscription_id = :subscription_id;
	END
	
	-- add subscription

	PROCEDURE add(
		IN subscription ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:subscription_data  = @FILTER(:subscription, subscription);
		
		
		INSERT INTO subscription 
			
			( @KEYS(:subscription_data) )
			
	  	VALUES ( :subscription_data );

	END
	
	-- edit subscription
	
	CREATE PROCEDURE edit(
		IN subscription ARRAY,
		IN subscription_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:subscription, subscription);

		UPDATE subscription
			
			SET @LIST(:subscription) 
			
		WHERE subscription_id = :subscription_id


	END
	
	
	-- delete subscription

	PROCEDURE delete(
		IN subscription_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- user_group
		DELETE FROM subscription WHERE subscription_id IN (:subscription_id);
	END
