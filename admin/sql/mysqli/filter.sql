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
			AND attribute_content.name LIKE CONCAT('%',:search,'%')
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



	-- price
	CREATE PROCEDURE price(
		IN site_id INT,
		IN taxonomy_item_id ARRAY,
		IN option_value_id ARRAY,
		IN product_attribute ARRAY,
		IN start INT,
		IN limit INT,
		OUT fetch_all
	)
	BEGIN

		SELECT MAX(p.price) as price_max_limit, MIN(p.price) as price_min_limit -- , tax_type_id 
			FROM product p 
				LEFT JOIN product_to_site p2s ON (p.product_id = p2s.product_id) 
				LEFT JOIN product_to_taxonomy_item p2t ON (p.product_id = p2t.product_id)
				LEFT JOIN product_option_value pov  ON (p.product_id = pov.product_id)
				LEFT JOIN product_attribute pa  ON (p.product_id = pa.product_id)
		WHERE 
			p2s.site_id = :site_id
		
			-- filter sql
			AND pov.product_id IN (
					SELECT product_id FROM product_option_value pov2 
					WHERE pov2.option_value_id IN (:option_value_id)
					-- GROUP by product_id HAVING count(product_id) >= 4
				) 
			
			AND pa.value IN (:product_attribute) 
			AND p2t.taxonomy_item_id IN (:taxonomy_item_id)

		-- limit
		@IF isset(:limit)
		THEN
		    LIMIT :limit

		    @IF isset(:start)
		    THEN
			OFFSET :start
		    END @IF
			
		END @IF;

	END
	


	CREATE PROCEDURE manufacturers(
		IN site_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all
	)
	BEGIN

		SELECT m.*,m.image as image FROM manufacturer m 
				LEFT JOIN manufacturer_to_site m2s ON (m.manufacturer_id = m2s.manufacturer_id) 
				INNER JOIN  product p  ON (m.manufacturer_id = p.manufacturer_id)
				LEFT JOIN product_option_value pov  ON (p.product_id = pov.product_id)
				LEFT JOIN product_attribute pa  ON (p.product_id = pa.product_id)
		WHERE 
		m2s.site_id = :site_id
		-- filter sql
		GROUP BY(m.manufacturer_id)		
		
		-- limit
		@IF isset(:limit)
		THEN
		    LIMIT :limit

		    @IF isset(:start)
		    THEN
			OFFSET :start
		    END @IF
			
		END @IF;
		
	END
	


	CREATE PROCEDURE categories(
		IN site_id INT,
		IN language_id INT,
		IN parent_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all
	)
	BEGIN

		SELECT ti.*,ti.taxonomy_item_id as taxonomy_item_id,count(p2t.product_id) as prod_count FROM taxonomy_item ti 
			LEFT JOIN taxonomy_to_site t2s ON (ti.taxonomy_item_id = t2s.taxonomy_item_id) 
			LEFT JOIN taxonomy_item_content tic ON (ti.taxonomy_item_id = tic.taxonomy_item_id) 
			LEFT JOIN product_to_taxonomy_item p2t ON (ti.taxonomy_item_id = p2t.taxonomy_item_id) 
		WHERE 
			ti.parent_id = :parent_id 
			AND tic.language_id = :language_id 
			AND t2s.site_id = :site_id 
			AND ti.status = 1 
		
		GROUP BY ti.taxonomy_item_id, tic.name ORDER BY ti.sort_order, tic.name
			
		@IF isset(:limit)
		THEN		
			LIMIT :limit OFFSET :start
		END @IF;
	END
	
	


	CREATE PROCEDURE filters(
		IN filter_id ARRAY,
		OUT fetch_all
	)
	BEGIN
		SELECT * FROM filter WHERE filter_id IN (:filter_id);
	END

	CREATE PROCEDURE availability(
		IN attribute_id ARRAY,
		OUT fetch_all
	)
	BEGIN
		SELECT * FROM `option` WHERE option_id IN (:attribute_id);
	END
	


	-- options
	CREATE PROCEDURE options(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all
	)
	BEGIN

		SELECT ov.*,oc.name as option_name, ovc.name as value_name, ov.image as value_image, count(p.product_id) as prod_count 
				FROM product_option_value pov 
			LEFT JOIN option_value ov ON (pov.option_value_id = ov.option_value_id) 
			LEFT JOIN option_value_content ovc ON (ov.option_value_id = ovc.option_value_id) 
			LEFT JOIN `option` po  ON (pov.option_id = po.option_id) 
			LEFT JOIN option_content oc ON (po.option_id = oc.option_id) 
			INNER JOIN product_to_taxonomy_item p2t ON (pov.product_id = p2t.product_id)
			LEFT JOIN product_attribute pa  ON (pov.product_id = pa.product_id)
			INNER JOIN  product p  ON (p.product_id = p2t.product_id) 
		WHERE ovc.language_id = :language_id
		AND oc.language_id = :language_id
		 
		 GROUP BY ov.option_value_id, oc.name, ovc.name, ov.image

		@IF isset(:limit)
		THEN		
			LIMIT :limit OFFSET :start
		END @IF;
			
	END
	

	CREATE PROCEDURE attributes(
		IN language_id INT,
		OUT fetch_all
	)
	BEGIN

		SELECT pa.attribute_id, ac.name, pa.value,count(pa.product_id) as prod_count FROM product_attribute pa 
			 
			 LEFT JOIN attribute_content ac ON (pa.attribute_id = ac.attribute_id)
			 WHERE ac.language_id = :language_id AND pa.language_id = :language_id 
			 AND product_id IN (
			 SELECT p.product_id FROM 
			 product p
			 INNER JOIN product_to_taxonomy_item p2t ON (p.product_id = p2t.product_id) 
			 ) 
		GROUP BY pa.attribute_id, ac.name, pa.value ORDER BY ac.name

	END
