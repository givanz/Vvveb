-- Options

	-- get all options

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- option
		SELECT "option".*, option_content.name
			FROM "option" AS "option"
			INNER JOIN option_content 
				ON option_content.option_id = "option".option_id AND option_content.language_id = :language_id
		
		WHERE 1 = 1
			
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT("option".option_id, "option") -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get option

	PROCEDURE get(
		IN option_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- option
		SELECT *
			FROM "option" as _ 
			INNER JOIN option_content 
				ON option_content.option_id = _.option_id AND option_content.language_id = :language_id

		WHERE _.option_id = :option_id;
	END
	
	-- add option

	PROCEDURE add(
		IN option ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:option_data  = @FILTER(:option, option);
		
		
		INSERT INTO "option" 
			
			( @KEYS(:option_data) )
			
	  	VALUES ( :option_data );		
		
		
		:option_content  = @FILTER(:option, option_content);
	  	
		INSERT INTO option_content 
			
			( @KEYS(:option_content), language_id, option_id )
			
	  	VALUES ( :option_content, :language_id, @result.option);


	END
	
	-- edit option
	CREATE PROCEDURE edit(
		IN option ARRAY,
		IN option_id INT,
		OUT affected_rows
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:option_data = @FILTER(:option, option);

		UPDATE "option"
			
			SET @LIST(:option_data) 
			
		WHERE option_id = :option_id;
		
		-- allow only table fields and set defaults for missing values
		:option_content = @FILTER(:option, option_content);

		UPDATE option_content
			
			SET @LIST(:option_content) 
			
		WHERE option_id = :option_id

	END
	
	
	-- delete option

	PROCEDURE delete(
		IN option_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- option
		DELETE FROM option_content WHERE option_id IN (:option_id);
		DELETE FROM "option" WHERE option_id IN (:option_id);
	END
