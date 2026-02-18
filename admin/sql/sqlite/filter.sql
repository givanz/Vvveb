-- Attributes

	-- get all attributes

	PROCEDURE getAll(
		IN language_id INT,
		IN product_id ARRAY,
		IN attribute_group_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
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
		
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(attribute.attribute_id, attribute) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get attribute

	PROCEDURE get(
		IN attribute_id INT,
		IN language_id INT,
		OUT fetch_row, 
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

	PROCEDURE add(
		IN attribute ARRAY,
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
		IN attribute ARRAY,
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

	PROCEDURE delete(
		IN attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- attribute
		DELETE FROM attribute_content WHERE attribute_id IN (:attribute_id);
		DELETE FROM attribute WHERE attribute_id IN (:attribute_id);
	END



	PROCEDURE price(
		IN site_id INT,
		IN category_id ARRAY,
		IN taxonomy_item_id ARRAY,
		IN option_value_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
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
			@SQL_LIMIT(:start, :limit)
		END @IF;

	END
	


	PROCEDURE manufacturers(
		IN attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN

		SELECT *,m.image as image FROM manufacturer m 
				LEFT JOIN manufacturer_to_site m2s ON (m.manufacturer_id = m2s.manufacturer_id) 
				INNER JOIN  product p  ON (m.manufacturer_id = p.manufacturer_id)
				LEFT JOIN product_special ps ON (p.product_id = ps.product_id)  
				LEFT JOIN product_to_category p2c ON (p.product_id = p2c.product_id)
				LEFT JOIN product_option_value pov  ON (p.product_id = pov.product_id)
				LEFT JOIN product_attribute pa  ON (p.product_id = pa.product_id)
		WHERE 
		m2s.site_id = :site_id
		-- filter sql
		GROUP BY(m.manufacturer_id)		
		
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
		
	END
	


	PROCEDURE categories(
		IN attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN

		SELECT *,c.category_id as category_id,count(p2c.product_id) as prod_count FROM category c 
			LEFT JOIN category_to_site c2s ON (c.category_id = c2s.category_id) 
			LEFT JOIN category_description cd ON (c.category_id = cd.category_id) 
			LEFT JOIN product_to_category p2c ON (c.category_id = p2c.category_id) 
		WHERE 
			c.parent_id = '" . (int)$parent_id . "' 
			AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			AND c2s.site_id = '" . (int)$this->config->get('config_site_id') . ' ' .  "'  
			AND c.status = '1' 
		
		GROUP BY c.category_id ORDER BY c.sort_order, LCASE(cd.name)
			
		-- limit
		@IF isset(:limit)
		THEN		
			@SQL_LIMIT(:start, :limit)
		END @IF;
	END
	
	


	PROCEDURE filters(
		IN attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- attribute
		DELETE FROM attribute_content WHERE attribute_id IN (:attribute_id);
		DELETE FROM attribute WHERE attribute_id IN (:attribute_id);
	END
	
	


	PROCEDURE availability(
		IN attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN
		-- attribute
		DELETE FROM attribute_content WHERE attribute_id IN (:attribute_id);
		DELETE FROM attribute WHERE attribute_id IN (:attribute_id);
	END
	
	


	PROCEDURE options(
		IN attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN

		SELECT *,od.name as option_name, ovd.name as value_name, ov.image as value_image, count(p.product_id) as prod_count 
				FROM product_option_value pov 
			LEFT JOIN option_value ov ON (pov.option_value_id = ov.option_value_id) 
			LEFT JOIN option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) 
			LEFT JOIN `option` po  ON (pov.option_id = po.option_id) 
			LEFT JOIN option_description od ON (po.option_id = od.option_id) 
			INNER JOIN product_to_category p2c ON (pov.product_id = p2c.product_id $category_join)
			LEFT JOIN product_attribute pa  ON (pov.product_id = pa.product_id)
			INNER JOIN  product p  ON (p.product_id = p2c.product_id) 
			LEFT JOIN product_special ps ON (p.product_id = ps.product_id) 
		WHERE ovd.language_id = :language_id
		 --. $filter_sql . " 
		 
		 GROUP BY ov.option_value_id
			
	END
	

	PROCEDURE attributes(
		IN attribute_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows, 
	)
	BEGIN

		SELECT pa1.attribute_id, ad.name, pa1.text,count(pa1.product_id) as prod_count FROM product_attribute pa1 
			 
			 LEFT JOIN attribute_description ad ON (pa1.attribute_id = ad.attribute_id)
			 WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa1.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			 AND product_id IN (
			 SELECT p.product_id FROM 
			 product p
			 INNER JOIN product_to_category p2c ON (p.product_id = p2c.product_id) 
			 
			 " . $sql_joins . "
			 -- ". $filter_sql .
			 ) 
		GROUP BY pa1.attribute_id,text ORDER BY ad.name

	END
