-- Currencies

	-- get all subscription status

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- subscription_status
		SELECT *
			FROM subscription_status AS subscription_status WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(subscription_status.subscription_status_id, subscription_status) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get subscription_status

	PROCEDURE get(
		IN subscription_status_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- subscription_status
		SELECT *
			FROM subscription_status as _ WHERE subscription_status_id = :subscription_status_id;
	END
	
	-- add subscription_status

	PROCEDURE add(
		IN subscription_status ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:subscription_status_data  = @FILTER(:subscription_status, subscription_status);
		
		
		INSERT INTO subscription_status 
			
			( @KEYS(:subscription_status_data), language_id )
			
	  	VALUES ( :subscription_status_data, :language_id );

	END
	
	-- edit subscription_status
	CREATE PROCEDURE edit(
		IN subscription_status ARRAY,
		IN subscription_status_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:subscription_status, subscription_status);

		UPDATE subscription_status 
			
			SET @LIST(:subscription_status) 
			
		WHERE subscription_status_id = :subscription_status_id


	END
	
	-- delete subscription_status

	PROCEDURE delete(
		IN subscription_status_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- subscription_status
		DELETE FROM subscription_status WHERE subscription_status_id IN (:subscription_status_id);
	END
