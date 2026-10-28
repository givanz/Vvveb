-- Attributes

	-- get all attribute_groups

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN product_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (attribute_group_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- attribute_group
		SELECT attribute_group_content.name, attribute_group_content.name as `group`, attribute_group.*
				@IF isset(:product_id)
				THEN		
					,pa.value
				END @IF		
		
			FROM attribute_group
			INNER JOIN attribute_group_content 
				ON attribute_group_content.attribute_group_id = attribute_group.attribute_group_id AND attribute_group_content.language_id = :language_id
				
			@IF isset(:product_id)
			THEN		
				LEFT JOIN attribute a ON a.attribute_group_id = attribute_group.attribute_group_id
				LEFT JOIN product_attribute pa ON pa.attribute_id = a.attribute_id
			END @IF
		
		WHERE 1 = 1
			
		@IF isset(:product_id)
		THEN		
			AND pa.product_id = :product_id
		END @IF

		-- cursor pagination

		@IF isset(:cursor)

		THEN

		    AND attribute_group.attribute_group_id > :cursor

		END @IF

		-- limit
		@IF isset(:limit)
		THEN
		    LIMIT :limit

		    @IF isset(:start)
		    THEN
			OFFSET :start
		    END @IF
			
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(attribute_group.attribute_group_id, attribute_group) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get attribute_group

	CREATE PROCEDURE get(
		IN attribute_group_id INT,
		IN language_id INT,
		OUT fetch_row 
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

	CREATE PROCEDURE add(
		IN attribute_group ROW,
		IN language_id INT,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:attribute_group_data  = @FILTER(:attribute_group, attribute_group)
		
		
		INSERT INTO attribute_group 
			
			@IF !empty(:attribute_group_data)
			THEN
			
				( @KEYS(:attribute_group_data) )
			
	  			VALUES ( :attribute_group_data )
			
			@ELSE
			
				DEFAULT VALUES
			
			END @IF;		
		
		
		:attribute_group_content  = @FILTER(:attribute_group, attribute_group_content)
	  	
		INSERT INTO attribute_group_content 
			
			( @KEYS(:attribute_group_content), language_id, attribute_group_id )
			
	  	VALUES ( :attribute_group_content, :language_id, @result.attribute_group);


	END
	
	-- edit attribute_group
	CREATE PROCEDURE edit(
		IN attribute_group ROW,
		IN attribute_group_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:attribute_group_data = @FILTER(:attribute_group, attribute_group)

		@IF !empty(:attribute_group_data)
		THEN
		UPDATE attribute_group
			
			SET @LIST(:attribute_group_data) 
			
		WHERE attribute_group_id = :attribute_group_id
		END @IF;
		
		-- allow only table fields and set defaults for missing values
		:attribute_group_content = @FILTER(:attribute_group, attribute_group_content)

		@IF !empty(:attribute_group_content)
		THEN
		UPDATE attribute_group_content
			
			SET @LIST(:attribute_group_content) 
			
		WHERE attribute_group_id = :attribute_group_id
		END @IF;

	END
	
	
	-- delete attribute_group

	CREATE PROCEDURE delete(
		IN attribute_group_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows 
	)
	BEGIN
		-- attribute_group
		DELETE FROM attribute_group_content WHERE attribute_group_id IN (:attribute_group_id);
		DELETE FROM attribute_group WHERE attribute_group_id IN (:attribute_group_id);
	END
