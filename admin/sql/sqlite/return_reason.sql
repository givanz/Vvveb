-- Return reason

	-- get all return reason

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- return_reason
		SELECT *
			FROM return_reason AS return_reason WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(return_reason.return_reason_id, return_reason) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get return reason

	PROCEDURE get(
		IN return_reason_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- return_reason
		SELECT *
			FROM return_reason as _ WHERE return_reason_id = :return_reason_id;
	END
	
	-- add return_reason

	PROCEDURE add(
		IN return_reason ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:return_reason_data  = @FILTER(:return_reason, return_reason);
		
		
		INSERT INTO return_reason 
			
			( @KEYS(:return_reason_data), language_id )
			
	  	VALUES ( :return_reason_data, :language_id );

	END
	
	-- edit return_reason
	CREATE PROCEDURE edit(
		IN return_reason ARRAY,
		IN return_reason_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:return_reason, return_reason);

		UPDATE return_reason 
			
			SET @LIST(:return_reason) 
			
		WHERE return_reason_id = :return_reason_id


	END
	
	-- delete return_reason

	PROCEDURE delete(
		IN return_reason_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- return_reason
		DELETE FROM return_reason WHERE return_reason_id IN (:return_reason_id);
	END
