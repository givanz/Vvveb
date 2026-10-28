-- Attributes

	-- get all attributes

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN product_id ARRAY,
		IN attribute_group_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (attribute_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- attribute
		SELECT attribute_content.name, attribute_group_content.name as `group`, attribute.*
				@IF isset(:product_id)
				THEN		
					, pa.product_id
					,pa.value
				END @IF		
		
			FROM attribute
			INNER JOIN attribute_content 
				ON attribute_content.attribute_id = attribute.attribute_id AND attribute_content.language_id = :language_id
			INNER JOIN attribute_group_content 
				ON attribute_group_content.attribute_group_id = attribute.attribute_group_id AND attribute_group_content.language_id = :language_id
				
			@IF isset(:product_id)
			THEN		
				LEFT JOIN product_attribute	pa ON attribute.attribute_id = pa.attribute_id
			END @IF
		
		WHERE 1 = 1
			
		@IF isset(:product_id)
		THEN		
			AND pa.product_id IN (:product_id)
		END @IF

		@IF isset(:attribute_group_id)
		THEN		
			AND attribute.attribute_group_id = :attribute_group_id
		END @IF

		-- search
		@IF isset(:search) AND !empty(:search)
		THEN 
			AND attribute_content.name LIKE '%' || :search || '%' 
		END @IF	  
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND attribute.attribute_id > :cursor
		
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
			
			@SQL_COUNT(attribute.attribute_id, attribute) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get attribute

	CREATE PROCEDURE get(
		IN attribute_id INT,
		IN language_id INT,
		OUT fetch_row
	)
	BEGIN
		-- attribute
		SELECT *
			FROM attribute as _ 
		INNER JOIN attribute_content ac
				ON ac.attribute_id = _.attribute_id AND ac.language_id = :language_id		
		
		WHERE _.attribute_id = :attribute_id;
	END
	
	-- add attribute

	CREATE PROCEDURE add(
		IN attribute ROW,
		IN language_id INT,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:attribute_data  = @FILTER(:attribute, attribute)
		
		
		INSERT INTO attribute 
			
			( @KEYS(:attribute_data) )
			
	  	VALUES ( :attribute_data );		
		
		
		:attribute_content  = @FILTER(:attribute, attribute_content)
	  	
		INSERT INTO attribute_content 
			
			( @KEYS(:attribute_content), language_id, attribute_id )
			
	  	VALUES ( :attribute_content, :language_id, @result.attribute);


	END
	
	-- edit attribute
	CREATE PROCEDURE edit(
		IN attribute ROW,
		IN attribute_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		:attribute_data = @FILTER(:attribute, attribute)

		UPDATE attribute
			
			SET @LIST(:attribute_data) 
			
		WHERE attribute_id = :attribute_id;
		
		-- allow only table fields and set defaults for missing values
		:attribute_content = @FILTER(:attribute, attribute_content)

		UPDATE attribute_content
			
			SET @LIST(:attribute_content) 
			
		WHERE attribute_id = :attribute_id;

	END
	
	
	-- delete attribute

	CREATE PROCEDURE delete(
		IN attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows 
	)
	BEGIN
		-- attribute
		DELETE FROM attribute_content WHERE attribute_id IN (:attribute_id);
		DELETE FROM attribute WHERE attribute_id IN (:attribute_id);
	END
