-- Digital assets log

	-- get all digital_assets_log

	PROCEDURE getAll(
		IN user_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN

		SELECT digital_asset_log.*
			
			FROM digital_asset_log

		WHERE 1 = 1
			
		-- user_id 
		@IF isset(:user_id)
		THEN	
			user_id = :user_id
		END @IF

		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(digital_asset.digital_asset_id, digital_asset) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get digital_asset

	PROCEDURE get(
		IN digital_asset_log_id INT,
		IN user_id INT,
		IN site_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- digital_asset
		SELECT *
			FROM digital_asset_log as _ 

		WHERE _.digital_asset_log_id = :digital_asset_log_id;
		
	END	
	
	-- add digital_asset_log

	PROCEDURE add(
		IN digital_asset_log ARRAY,
		OUT fetch_one,
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:digital_asset_log_data  = @FILTER(:digital_asset_log, digital_asset_log)
		
		INSERT INTO digital_asset_log 
			
			( @KEYS(:digital_asset_log_data) )
			
	  	VALUES ( :digital_asset_log_data )  RETURNING digital_asset_log_id;		

	END
	
	-- edit digital_asset_log
	
	CREATE PROCEDURE edit(
		IN digital_asset_log ARRAY,
		IN digital_asset_log_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:digital_asset_log_data = @FILTER(:digital_asset_log, digital_asset_log)

		UPDATE digital_asset_log
			
			SET @LIST(:digital_asset_log_data) 
			
		WHERE digital_asset_log_id = :digital_asset_log_id;

	END
	
	
	-- delete digital_asset_log

	PROCEDURE delete(
		IN digital_asset_log_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- digital_asset_log
		DELETE FROM digital_asset_log WHERE digital_asset_log_id IN (:digital_asset_log_id);
	END
