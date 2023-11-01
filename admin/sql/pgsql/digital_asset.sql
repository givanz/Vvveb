-- Digital assets

	-- get all digital_assets

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- digital_asset
		SELECT digital_asset.*, digital_asset_content.name
			FROM digital_asset AS digital_asset
			INNER JOIN digital_asset_content ON digital_asset_content.digital_asset_id = digital_asset.digital_asset_id 
												 AND digital_asset_content.language_id = :language_id
		
		WHERE 1 = 1
			
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
		IN digital_asset_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- digital_asset
		SELECT *
			FROM digital_asset as _ 
		INNER JOIN digital_asset_content ON digital_asset_content.digital_asset_id = _.digital_asset_id 
										 AND digital_asset_content.language_id = :language_id
		
		WHERE _.digital_asset_id = :digital_asset_id;
	END
	
	-- add digital_asset

	PROCEDURE add(
		IN digital_asset ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:digital_asset_data  = @FILTER(:digital_asset, digital_asset);
		
		
		INSERT INTO digital_asset 
			
			( @KEYS(:digital_asset_data) )
			
	  	VALUES ( :digital_asset_data );

		
		:digital_asset_content  = @FILTER(:digital_asset, digital_asset_content);
	  	
		INSERT INTO digital_asset_content 
			
			( @KEYS(:digital_asset_content), language_id, digital_asset_id )
			
	  	VALUES ( :digital_asset_content, :language_id, @result.digital_asset);


	END
	
	-- edit digital_asset
	CREATE PROCEDURE edit(
		IN digital_asset ARRAY,
		IN digital_asset_id INT,
		OUT affected_rows
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:digital_asset_data = @FILTER(:digital_asset, digital_asset);

		UPDATE digital_asset
			
			SET @LIST(:digital_asset_data) 
			
		WHERE digital_asset_id = :digital_asset_id;

		-- allow only table fields and set defaults for missing values
		:digital_asset_content = @FILTER(:digital_asset, digital_asset_content);

		UPDATE digital_asset_content
			
			SET @LIST(:digital_asset_content) 
			
		WHERE digital_asset_id = :digital_asset_id;

	END
	
	
	-- delete digital_asset

	PROCEDURE delete(
		IN digital_asset_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- digital_asset
		DELETE FROM digital_asset_content WHERE digital_asset_id IN (:digital_asset_id);
		DELETE FROM digital_asset WHERE digital_asset_id IN (:digital_asset_id);
	END
