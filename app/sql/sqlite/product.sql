-- Product

	import(/admin/product.sql);


	-- get all languages content

	CREATE PROCEDURE getContent(
		IN product_id INT,
        IN slug CHAR,
		OUT fetch_all,
	)
	BEGIN

		SELECT _.product_id,_.slug,_.name,_.meta_keywords,_.meta_description,_.language_id,product.template,language.code,language.code as array_key
			FROM product_content AS _
			LEFT JOIN language ON (language.language_id = _.language_id)
			LEFT JOIN product ON (product.product_id = _.product_id)
		WHERE 1 = 1

            @IF isset(:slug)
			THEN 
				AND _.product_id = (SELECT product_id FROM product_content WHERE slug = :slug LIMIT 1)
        	END @IF			

            @IF isset(:product_id)
			THEN
                AND _.product_id = :product_id
        	END @IF			
	END
