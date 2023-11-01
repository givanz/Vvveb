-- Attributes

	-- get all attribute_groups

	PROCEDURE getAll(
		IN language_id INT,
		IN product_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- attribute_group
		SELECT attribute_group_content.name, attribute_group_content.name as `group`, attribute_group.*
				@IF isset(:product_id)
				THEN		
					,pa.value
				END @IF		
		
			FROM attribute_group AS attribute_group
			INNER JOIN attribute_group_content 
				ON attribute_group_content.attribute_group_id = attribute_group.attribute_group_id AND attribute_group_content.language_id = :language_id
				
			@IF isset(:product_id)
			THEN		
				LEFT JOIN product_attribute_group pa ON attribute_group.attribute_group_id = pa.attribute_group_id
			END @IF
		
		WHERE 1 = 1
			
		@IF isset(:product_id)
		THEN		
			AND pa.product_id = :product_id
		END @IF

		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(attribute_group.attribute_group_id, attribute_group) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get attribute_group

	PROCEDURE get(
		IN attribute_group_id INT,
		IN language_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- attribute_group
		SELECT *
			FROM attribute_group as _ 
		INNER JOIN attribute_group_content agc
				ON agc.attribute_group_id = _.attribute_group_id AND agc.language_id = :language_id			
				
		WHERE _.attribute_group_id = :attribute_group_id;
	END
	
	-- add attribute_group

	PROCEDURE add(
		IN attribute_group ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:attribute_group_data  = @FILTER(:attribute_group, attribute_group);
		
		
		INSERT INTO attribute_group 
			
			( @KEYS(:attribute_group_data) )
			
	  	VALUES ( :attribute_group_data );		
		
		
		:attribute_group_content  = @FILTER(:attribute_group, attribute_group_content);
	  	
		INSERT INTO attribute_group_content 
			
			( @KEYS(:attribute_group_content), language_id, attribute_group_id )
			
	  	VALUES ( :attribute_group_content, :language_id, @result.attribute_group);


	END
	
	-- edit attribute_group
	CREATE PROCEDURE edit(
		IN attribute_group ARRAY,
		IN attribute_group_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:attribute_group_data = @FILTER(:attribute_group, attribute_group);

		UPDATE attribute_group
			
			SET @LIST(:attribute_group_data) 
			
		WHERE attribute_group_id = :attribute_group_id;
		
		-- allow only table fields and set defaults for missing values
		:attribute_group_content = @FILTER(:attribute_group, attribute_group_content);

		UPDATE attribute_group_content
			
			SET @LIST(:attribute_group_content) 
			
		WHERE attribute_group_id = :attribute_group_id;

	END
	
	
	-- delete attribute_group

	PROCEDURE delete(
		IN attribute_group_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- attribute_group
		DELETE FROM attribute_group_content WHERE attribute_group_id IN (:attribute_group_id);
		DELETE FROM attribute_group WHERE attribute_group_id IN (:attribute_group_id);
	END
