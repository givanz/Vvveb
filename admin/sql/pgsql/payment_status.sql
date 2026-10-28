-- Payment statuses

	-- get all payment statuses

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (payment_status_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		SELECT *
			FROM payment_status WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND payment_status.payment_status_id > :cursor
		
		END @IF
		
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(payment_status.payment_status_id, payment_status) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get payment_status

	CREATE PROCEDURE get(
		IN payment_status_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- payment_status
		SELECT *
			FROM payment_status as _ WHERE payment_status_id = :payment_status_id;
	END
	
	-- add payment_status

	CREATE PROCEDURE add(
		IN payment_status ROW,
		IN language_id INT,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:payment_status_data  = @FILTER(:payment_status, payment_status)
		
		
		INSERT INTO payment_status 
			
			( @KEYS(:payment_status_data), language_id )
			
	  	VALUES ( :payment_status_data, :language_id ) RETURNING payment_status_id;

	END
	
	-- edit payment_status
	CREATE PROCEDURE edit(
		IN payment_status ROW,
		IN payment_status_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:payment_status, payment_status)

		UPDATE payment_status 
			
			SET @LIST(:payment_status) 
			
		WHERE payment_status_id = :payment_status_id


	END
	
	-- delete payment_status

	CREATE PROCEDURE delete(
		IN payment_status_id ARRAY,
		OUT affected_rows 
	)
	BEGIN
		-- payment_status
		DELETE FROM payment_status WHERE payment_status_id IN (:payment_status_id);
	END
