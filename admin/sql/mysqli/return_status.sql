-- Return status

	-- get all return status

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- return_status
		SELECT *
			FROM return_status AS return_status WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(return_status.return_status_id, return_status) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get return_status

	PROCEDURE get(
		IN return_status_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- return_status
		SELECT *
			FROM return_status as _ WHERE return_status_id = :return_status_id;
	END
	
	-- add return_status

	PROCEDURE add(
		IN return_status ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:return_status_data  = @FILTER(:return_status, return_status);
		
		
		INSERT INTO return_status 
			
			( @KEYS(:return_status_data), language_id )
			
	  	VALUES ( :return_status_data, :language_id );

	END
	
	-- edit return status

	CREATE PROCEDURE edit(
		IN return_status ARRAY,
		IN return_status_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:return_status, return_status);

		UPDATE return_status 
			
			SET @LIST(:return_status) 
			
		WHERE return_status_id = :return_status_id


	END
	
	-- delete return_status

	PROCEDURE delete(
		IN return_status_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- return_status
		DELETE FROM return_status WHERE return_status_id IN (:return_status_id);
	END
