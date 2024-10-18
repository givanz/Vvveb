-- Currencies

	-- get all currencies

	PROCEDURE getAll(
		IN currency_id INT,
		IN status INT,
		OUT fetch_row, 
		OUT fetch_one,
	)
	BEGIN
		-- currency
		SELECT *, code as array_key
			FROM currency WHERE 1 = 1
			
		@IF isset(:status) AND :status != "" 
		THEN			
			AND status = :status
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(currency.currency_id, currency) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get currency

	PROCEDURE get(
		IN currency_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- currency
		SELECT *
			FROM currency as _ WHERE currency_id = :currency_id;
	END
	
	-- add currency

	PROCEDURE add(
		IN currency ARRAY,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:currency_data  = @FILTER(:currency, currency)
		
		
		INSERT INTO currency 
			
			( @KEYS(:currency_data) )
			
	  	VALUES ( :currency_data ) RETURNING currency_id;

	END
	
	-- edit currency
	CREATE PROCEDURE edit(
		IN currency ARRAY,
		IN currency_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:currency, currency)

		UPDATE currency 
			
			SET @LIST(:currency) 
			
		WHERE currency_id = :currency_id


	END
	
	-- delete currency

	PROCEDURE delete(
		IN currency_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- currency
		DELETE FROM currency WHERE currency_id IN (:currency_id);
	END
