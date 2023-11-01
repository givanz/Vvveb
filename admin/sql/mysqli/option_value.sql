-- Options

	-- get all option_values

	PROCEDURE getAll(
		IN language_id INT,
		IN option_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- option_value
		SELECT option_value.*, option_value_content.name
			FROM option_value AS option_value
			INNER JOIN option_value_content 
				ON option_value_content.option_value_id = option_value.option_value_id AND option_value_content.language_id = :language_id
		
		WHERE 1 = 1
			
		-- option_id
		@IF isset(:option_id)
		THEN		
			AND option_value.option_id = :option_id
		END @IF		
		
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(option_value.option_value_id, option_value) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get option_value

	PROCEDURE get(
		IN option_value_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- option_value
		SELECT *
			FROM option_value as _ 
			INNER JOIN option_value_content 
				ON option_value_content.option_value_id = _.option_value_id AND option_value_content.language_id = :language_id

		WHERE _.option_value_id = :option_value_id;
	END
	
	-- add option_value

	PROCEDURE add(
		IN option_value ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:option_value_data  = @FILTER(:option_value, option_value);
		
		
		INSERT INTO option_value 
			
			( @KEYS(:option_value_data) )
			
	  	VALUES ( :option_value_data );		
		
		
		:option_value_content  = @FILTER(:option_value, option_value_content);
	  	
		INSERT INTO option_value_content 
			
			( @KEYS(:option_value_content), language_id, option_value_id )
			
	  	VALUES ( :option_value_content, :language_id, @result.option_value);


	END
	
	-- edit option_value
	CREATE PROCEDURE edit(
		IN option_value ARRAY,
		IN option_value_id INT,
		OUT affected_rows
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:option_value_data = @FILTER(:option_value, option_value);

		UPDATE option_value
			
			SET @LIST(:option_value_data) 
			
		WHERE option_value_id = :option_value_id;
		
		-- allow only table fields and set defaults for missing values
		:option_value_content = @FILTER(:option_value, option_value_content);

		UPDATE option_value_content
			
			SET @LIST(:option_value_content) 
			
		WHERE option_value_id = :option_value_id;

	END
	
	
	-- delete option_value

	PROCEDURE delete(
		IN option_value_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- option_value
		DELETE FROM option_value_content WHERE option_value_id IN (:option_value_id);
		DELETE FROM option_value WHERE option_value_id IN (:option_value_id);
	END
