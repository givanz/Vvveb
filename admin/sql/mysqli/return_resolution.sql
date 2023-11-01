-- Return resolutions

	-- get all return resolution

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- return resolution
		SELECT *
			FROM return_resolution AS return_resolution WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(return_resolution.return_resolution_id, return_resolution) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get return resolution

	PROCEDURE get(
		IN return_resolution_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- return resolution
		SELECT *
			FROM return_resolution as _ WHERE return_resolution_id = :return_resolution_id;
	END
	
	-- add return_resolution

	PROCEDURE add(
		IN return_resolution ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:return_resolution_data  = @FILTER(:return_resolution, return_resolution);
		
		
		INSERT INTO return_resolution 
			
			( @KEYS(:return_resolution_data), language_id )
			
	  	VALUES ( :return_resolution_data, :language_id );

	END
	
	-- edit return_resolution
	CREATE PROCEDURE edit(
		IN return_resolution ARRAY,
		IN return_resolution_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:return_resolution, return_resolution);

		UPDATE return_resolution 
			
			SET @LIST(:return_resolution) 
			
		WHERE return_resolution_id = :return_resolution_id


	END
	
	-- delete return_resolution

	PROCEDURE delete(
		IN return_resolution_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- return_resolution
		DELETE FROM return_resolution WHERE return_resolution_id IN (:return_resolution_id);
	END
