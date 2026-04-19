-- Product

	import(/admin/product.sql);


	-- get all languages content

	CREATE PROCEDURE getContent(
		IN product_id INT,
		IN status INT,
		IN site_id INT,		
		IN slug CHAR,
		IN type CHAR,
		OUT fetch_all,
	)
	BEGIN

		SELECT product.*, _.product_id,_.slug,_.name,_.content,_.meta_keywords,_.meta_description,_.language_id,product.template,language.code,language.slug as language,language.slug as array_key
			FROM product_content AS _
			LEFT JOIN language ON (language.language_id = _.language_id)
			LEFT JOIN product ON (product.product_id = _.product_id)

			@IF isset(:site_id)
			THEN
				LEFT JOIN product_to_site pt ON (pt.product_id = _.product_id)
			END @IF	
			
		WHERE 1 = 1

        	@IF isset(:status)
        	THEN 
			AND product.status = :status
        	END @IF	        	
			
		@IF isset(:type) && :type
        	THEN 
			AND product.type = :type
        	END @IF			

        	@IF isset(:slug) && !(isset(:product_id) && :product_id) 
        	THEN 
			AND _.product_id = (SELECT product_id FROM product_content WHERE slug = :slug LIMIT 1)
        	END @IF			

        	@IF isset(:product_id) && :product_id > 0
        	THEN
			AND _.product_id = :product_id
        	END @IF
			
        	@IF isset(:site_id)
        	THEN
			AND pt.site_id = :site_id
        	END @IF		
	END
