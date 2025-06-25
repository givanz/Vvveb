-- Products

	-- get one product

	PROCEDURE get(
		IN product_id INT,
		IN slug CHAR,
		IN type CHAR,
		IN language_id INT,
		IN promotion INT,     -- include promotional price
		IN variant INT,       -- include variants
		IN variant_price INT, -- include variants min max prices
		IN points INT,        -- include points
		IN stock_status INT,  -- include stock_status
		IN weight_type INT,   -- include weight type
		IN length_type INT,   -- include length type
		IN rating INT,        -- include rating average
		IN reviews INT,       -- include reviews count
		
		OUT fetch_row, -- product
		OUT fetch_all, -- product_content
		OUT fetch_all, -- product_image
		OUT fetch_all, -- product_related
		OUT fetch_all, -- product_variant
		OUT fetch_all, -- product_subscription
		OUT fetch_all, -- product_attribute
		OUT fetch_all, -- digital_asset
		OUT fetch_all, -- product_discount
		OUT fetch_all, -- product_promotion
		OUT fetch_all, -- product_points
		OUT fetch_all, -- product_option
		OUT fetch_all, -- product_option_value
		OUT fetch_all, -- option_value_content
		OUT fetch_all  -- product_to_site
	)
	BEGIN
		-- product
		SELECT pc.*,_.*, _.product_id, 
			mf.slug as manufacturer_slug, mf.name as manufacturer_name,
			vd.slug as vendor_slug, vd.name as vendor_name,
			st.name as stock_status_name
			
			-- include promotional price 	
			@IF !empty(:promotion) && !empty(:user_group_id) 
			THEN 
				,(SELECT pp.price FROM product_promotion pp 
					WHERE pp.product_id = _.product_id AND pp.user_group_id = :user_group_id 
						AND (
							(pp.from_date = NULL OR pp.from_date < NOW()) 
							AND (pp.to_date = NULL OR pp.to_date > NOW())
						) 
					ORDER BY pp.priority ASC, pp.price ASC 
					LIMIT 1
				) AS promotion				
			END @IF			
			
			-- include variant price 	
			@IF !empty(:variant_price)
			THEN 
				,(SELECT MIN(pvmin.price) FROM product_variant pvmin 
					WHERE pvmin.product_id = _.product_id
					LIMIT 1
				) AS min_price		
				
				,(SELECT MAX(pvmax.price) FROM product_variant pvmax 
					WHERE pvmax.product_id = _.product_id
					LIMIT 1
				) AS max_price				
			END @IF

			-- include points 	
			@IF !empty(:points) && !empty(:user_group_id) 
			THEN 
			
			  ,(SELECT points
			   FROM product_points pp
			   WHERE pp.product_id = _.product_id
				 AND pp.user_group_id = :user_group_id
			   AS points
			   
			END @IF
			
			-- include stock_status 	
			@IF !empty(:stock_status)
			THEN 

			  ,(SELECT ss.name
			   FROM stock_status ss
			   WHERE ss.stock_status_id = _.stock_status_id
				 AND ss.language_id = :language_id) 
			  AS stock_status
			   
			END @IF


			-- include weight_type 	
			@IF !empty(:weight_type)
			THEN 
			
			  ,(SELECT wcd.unit
			   FROM weight_type_content wcd
			   WHERE _.weight_type_id = wcd.weight_type_id
				 AND wcd.language_id = :language_id) 
			   AS weight_type
			   
			END @IF


			-- include length_type 	
			@IF !empty(:length_type)
			THEN 
			
			  ,(SELECT lcd.unit
			   FROM length_type_content lcd
			   WHERE _.length_type_id = lcd.length_type_id
				 AND lcd.language_id = :language_id) 
			   AS length_type
			   
			END @IF
		
		
			-- include rating
			@IF !empty(:rating)
			THEN 
			
			  ,(SELECT AVG(rating) AS total
			   FROM product_review prvr
			   WHERE prvr.product_id = _.product_id
				 AND prvr.status = '1'
			   GROUP BY prvr.product_id) 
			  AS rating

			   
			END @IF
		
			-- include reviews
			@IF !empty(:reviews)
			THEN 

			  ,(SELECT COUNT(*) AS total
			   FROM product_review prv
			   WHERE prv.product_id = _.product_id
				 AND prv.status = 1
			   GROUP BY prv.product_id) AS reviews
									
			   
			END @IF			

		FROM product as _ -- (underscore) _ means that data will be kept in main array ['data'] and not default ['product'=>['data']]
		LEFT JOIN product_content pc ON (
				_.product_id = pc.product_id
		
				@IF isset(:language_id)
				THEN
					AND pc.language_id = :language_id
				END @IF
			)  			
		LEFT JOIN manufacturer mf ON ( mf.manufacturer_id = _.manufacturer_id)
		LEFT JOIN vendor vd ON ( vd.vendor_id = _.vendor_id)
		LEFT JOIN stock_status st ON ( st.stock_status_id = _.stock_status_id AND st.language_id = :language_id)
	
		WHERE  1 = 1

            @IF isset(:slug) && !(isset(:product_id) && :product_id) 
            THEN 
                AND pc.slug = :slug 
            END @IF			

            @IF isset(:product_id) && :product_id > 0
            THEN 
                AND _.product_id = :product_id
            END @IF	           
			
            @IF isset(:type) && !empty(:type)
            THEN 
                AND _.type = :type
            END @IF		
        
        LIMIT 1;

		-- content, use @result to make sure we have a product_id if the product is selected by slug
		SELECT *,language_id, language_id as array_key -- array_key column means that this column (language_id) value will be used as array key when adding row to result array
			FROM product_content 
		WHERE product_id = @result.product_id;	 

		-- images
		SELECT image, product_image_id as id, sort_order -- , product_image_id as array_key -- product_image_id will be used as key
			FROM product_image 
		WHERE product_id = @result.product_id ORDER BY sort_order;

		-- related
		SELECT product_related.product_related_id,product_related.product_id, pc.name,pc.slug, product_related_id as id -- , product_image_id as product_related_id -- product_image_id will be used as key
			FROM product_related
			LEFT JOIN product_content pc ON (
				product_related.product_related_id = pc.product_id
		
				@IF isset(:language_id)
				THEN
					AND pc.language_id = :language_id
				END @IF
			)
		
		WHERE product_related.product_id = @result.product_id;			
			
		-- variant
		SELECT *, options as array_key
			FROM product_variant
		WHERE product_variant.product_id = @result.product_id;		
		
		-- subscription
		SELECT product_subscription.*
			FROM product_subscription
		WHERE product_subscription.product_id = @result.product_id;		
		
		
		-- attribute
		SELECT 
				product_attribute.*, 
				ac.name,
				ac.language_id
		FROM product_attribute
			LEFT JOIN attribute_content ac ON (
				ac.attribute_id = product_attribute.attribute_id
		
				@IF isset(:language_id)
				THEN
					AND ac.language_id = :language_id
				END @IF
			)	
			
		WHERE product_attribute.product_id = @result.product_id;		
		
		
		-- digital asset
		SELECT 
				product_to_digital_asset.*, 
				dc.name,
				dc.digital_asset_id
		FROM product_to_digital_asset
			LEFT JOIN digital_asset_content dc ON (
				dc.digital_asset_id = product_to_digital_asset.digital_asset_id
		
				@IF isset(:language_id)
				THEN
					AND dc.language_id = :language_id
				END @IF
			)	
			
		WHERE product_to_digital_asset.product_id = @result.product_id;


		-- discount
		SELECT 
				product_discount.* 
		FROM product_discount
		WHERE product_discount.product_id = @result.product_id;	
		
		-- product promotion
		SELECT 
				product_promotion.* 
		FROM product_promotion
		WHERE product_promotion.product_id = @result.product_id;			

		-- points
		SELECT 
				product_points.* 
		FROM product_points
		WHERE product_points.product_id = @result.product_id;			
		
		-- product_option
		SELECT 
				product_option.product_option_id as array_key,
				product_option.*, 
				oc.name,
				`option`.type,
				`option`.sort_order
		FROM product_option
			LEFT JOIN `option` ON `option`.option_id = product_option.option_id 
			LEFT JOIN option_content oc ON oc.option_id = product_option.option_id AND oc.language_id = :language_id
		WHERE product_option.product_id = @result.product_id;			
		
		-- product_option_value
		SELECT *
			FROM product_option_value
		WHERE product_option_value.product_id = @result.product_id;			
		
		-- product_option_value
		SELECT *
			FROM option_value_content
		LEFT JOIN product_option po ON option_value_content.option_id = po.option_id
		WHERE po.product_id = @result.product_id AND option_value_content.language_id = :language_id;	

		-- product_to_site
		SELECT site_id as array_key, site_id FROM product_to_site
			WHERE product_to_site.product_id = @result.product_id;	 

	END
	

	PROCEDURE getData(
		IN product_id INT,
		IN manufacturer_id INT,
		IN vendor_id INT,
		
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		
		OUT fetch_one, 
		OUT fetch_one, 
	)
	BEGIN
	
		-- tax_type
		SELECT 
		
			name,  tax_type_id as array_key, 
			name as array_value
			
		FROM tax_type as tax_type_id; -- (underscore) _ means that data will be kept in main array
		
		
		-- weight_type
		SELECT 
		
			*, weight_type_id.weight_type_id as array_key,
			weight_desc.name as array_value -- only set name as value and return 
			
		FROM weight_type as weight_type_id
			LEFT JOIN weight_type_content as weight_desc
				ON weight_type_id.weight_type_id = weight_desc.weight_type_id;
					
		-- length_type
		SELECT 
		
			*, length_type_id.length_type_id as array_key,
			length_desc.name as array_value -- only set name as value and return 
			
		FROM length_type as length_type_id
			LEFT JOIN length_type_content as length_desc
				ON length_type_id.length_type_id = length_desc.length_type_id;
			
			
		-- stock status	
		SELECT 
		
			stock_status_id as array_key, -- stock_status_id as key
			name as array_value -- only set name as value and return  
			
		FROM stock_status as stock_status_id;		
		
		-- user group
		SELECT 
		
			user_group.user_group_id as array_key, -- stock_status_id as key
			name as array_value -- name as value
			
		FROM user_group
		INNER JOIN user_group_content ON user_group_content.user_group_id = user_group.user_group_id;	
		
		-- subscription plan	
		SELECT subscription_plan.subscription_plan_id as array_key,
				subscription_plan_content.name as array_value
			FROM subscription_plan
		INNER JOIN subscription_plan_content ON subscription_plan_content.subscription_plan_id = subscription_plan.subscription_plan_id 
												 AND subscription_plan_content.language_id = :language_id 
		-- LIMIT 100
		;		

		SELECT 
			`option`.option_id as array_key, -- option_id as key
			option_content.name,
			`option`.type
			FROM `option`
		INNER JOIN option_content 
				ON option_content.option_id = `option`.option_id AND option_content.language_id = :language_id
		-- LIMIT 100
		;		

		SELECT name FROM manufacturer AS manufacturer_id_text WHERE manufacturer_id = :manufacturer_id LIMIT 1;
		
		SELECT name FROM vendor AS vendor_id_text WHERE vendor_id = :vendor_id LIMIT 1;
		
		--<?php
		--	:results['status'] = [];
		--?>

	END
	
	
	-- Delete product

	PROCEDURE delete(
		IN  product_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM product_to_site WHERE product_id IN (:product_id);
		DELETE FROM product_image WHERE product_id IN (:product_id);
		DELETE FROM product_content WHERE product_id IN (:product_id);
		DELETE FROM product WHERE product_id IN (:product_id);
		
	END	
	
	-- Edit product

	PROCEDURE edit(
		IN product ARRAY,
		IN product_content ARRAY,
		IN taxonomy_item_id ARRAY,
		IN product_id INT,
		IN site_id ARRAY,
		OUT insert_id,
		OUT affected_rows,
		OUT insert_id,
		OUT affected_rows,
		OUT insert_id,
		OUT affected_rows
	)
	BEGIN
		:product_content  = @FILTER(:product_content, product_content, false)
		
		@EACH(:product_content) 
			INSERT INTO product_content 
		
				( @KEYS(:each), product_id)
			
			VALUES ( :each, :product_id)

			ON DUPLICATE KEY UPDATE @LIST(:each);


		@IF isset(:taxonomy_item_id) 
		THEN
			DELETE FROM product_to_taxonomy_item WHERE product_id = :product_id
		END @IF;

		@EACH(:taxonomy_item_id) 
			INSERT INTO product_to_taxonomy_item 
		
				( taxonomy_item_id, product_id)
			
			VALUES ( :each, :product_id)
			ON DUPLICATE KEY UPDATE taxonomy_item_id = :each;


		@IF isset(:site_id) 
		THEN
			DELETE FROM product_to_site WHERE product_id = :product_id
		END @IF;

		@EACH(:site_id) 
			INSERT INTO product_to_site 
			
				( product_id, site_id )
				
			VALUES ( :product_id, :each );

		-- SELECT * FROM product_option WHERE product_id = :product_id;
		

		-- SELECT * FROM product_option WHERE product_id = :product_id;

		-- allow only table fields and set defaults for missing values
		:product_update  = @FILTER(:product, product, false)

		@IF :product_update
		THEN
			UPDATE product 
				
				SET @LIST(:product_update) 
				
			WHERE product_id = :product_id
		END @IF;
		
	END	


	-- Edit post content

	CREATE PROCEDURE editContent(
		IN product_content ARRAY,
		IN product_id INT,
		IN language_id INT,
		OUT affected_rows
	)
	BEGIN
	
		:product_content  = @FILTER(:product_content, product_content)
	
		UPDATE product_content 
			
			SET @LIST(:product_content) 
			
		WHERE product_id = :product_id AND language_id = :language_id
	END
	

	-- Add new product

	CREATE PROCEDURE add(
		IN product ARRAY,
		IN product_content ARRAY,
		IN taxonomy_item_id ARRAY,
		IN site_id ARRAY,
		OUT insert_id,
		OUT insert_id,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_data  = @FILTER(:product, product)
		
		INSERT INTO product 
		
			( @KEYS(:product_data) )
			
		VALUES ( :product_data );
			

		:product_content = @FILTER(:product_content, product_content, false, true)


		@EACH(:product_content) 
			INSERT INTO product_content 
		
				( @KEYS(:each), product_id )
			
			VALUES ( :each, @result.product );
		
		@EACH(:taxonomy_item_id) 
			INSERT INTO product_to_taxonomy_item 
		
				( taxonomy_item_id, product_id)
			
			VALUES ( :each, @result.product)
			ON DUPLICATE KEY UPDATE taxonomy_item_id = :each;

		@EACH(:site_id) 
			INSERT INTO product_to_site 
			
				( product_id, site_id )
				
			VALUES ( @result.product, :each );

	END

	-- Edit product

	PROCEDURE productImage(
		IN product_image ARRAY,
		IN product_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_image WHERE product_id = :product_id;

		@EACH(:product_image) 
			INSERT INTO product_image 
		
				( image, product_id)
			
			VALUES ( :each, :product_id );

		-- ON DUPLICATE KEY UPDATE image = :each;
		
	END		
	
	-- Edit product related

	PROCEDURE productRelated(
		IN product_related ARRAY,
		IN product_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_related WHERE product_id = :product_id;
		
		@EACH(:product_related) 
			INSERT INTO product_related 
		
				( product_related_id, product_id)
			
			VALUES ( :each, :product_id );

			-- ON DUPLICATE KEY UPDATE product_related_id = :each;
		
	END		

	-- Edit product variant

	PROCEDURE productVariant(
		IN product_variant ARRAY,
		IN product_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_variant WHERE product_id = :product_id;
		
		@EACH(:product_variant) 
			INSERT INTO product_variant 
		
				( @KEYS(:each), product_id)
			
			VALUES ( :each, :product_id );
		
	END	
	
	-- Edit product subscription

	PROCEDURE productSubscription(
		IN product_subscription ARRAY,
		IN product_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_subscription WHERE product_id = :product_id;
		
		@EACH(:product_subscription) 
			INSERT INTO product_subscription 
		
				( @KEYS(:each), product_id)
			
			VALUES ( :each, :product_id );

	END
	
	-- Edit product discount

	PROCEDURE productDiscount(
		IN product_discount ARRAY,
		IN product_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_discount WHERE product_id = :product_id;
		
		@EACH(:product_discount) 
			INSERT INTO product_discount 
		
				( @KEYS(:each), product_id)
			
			VALUES ( :each, :product_id );
		
	END	
	
	-- Edit product promotion

	PROCEDURE productPromotion(
		IN product_promotion ARRAY,
		IN product_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_promotion WHERE product_id = :product_id;
		
		@EACH(:product_promotion) 
			INSERT INTO product_promotion 
		
				( @KEYS(:each), product_id)
			
			VALUES ( :each, :product_id );
		
	END	
	
	-- Edit product points

	PROCEDURE productPoints(
		IN product_points ARRAY,
		IN product_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_points WHERE product_id = :product_id;
		
		@EACH(:product_points) 
			INSERT INTO product_points 
		
				( @KEYS(:each), product_id)
			
			VALUES ( :each, :product_id );

	END		
	
	-- Edit product attribute

	PROCEDURE productAttribute(
		IN product_attribute ARRAY,
		IN product_id INT,
		IN language_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_attribute WHERE product_id = :product_id;
		
		:product_attribute = @FILTER(:product_attribute, product_attribute, false, true)
		
		@EACH(:product_attribute) 
			INSERT INTO product_attribute 
		
				( @KEYS(:each), product_id)
			
			VALUES ( :each, :product_id );
		
	END		
	
	-- Edit product digital asset

	PROCEDURE productDigitalAsset(
		IN product_digital_asset ARRAY,
		IN product_id INT,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM product_to_digital_asset WHERE product_id = :product_id;
		
		@EACH(:product_digital_asset) 
			INSERT INTO  product_to_digital_asset 
		
				( digital_asset_id, product_id)
			
			VALUES ( :each, :product_id );

			-- ON DUPLICATE KEY UPDATE product_digital_asset_id = :each;
		
	END		
	
	-- get all products 

	PROCEDURE getAll(

		-- variables
		IN language_id INT,
		IN user_group_id INT,
		IN site_id INT,
		IN admin_id INT,
		IN product_id ARRAY,
		IN taxonomy_item_id ARRAY,
		IN manufacturer_id ARRAY,
		IN vendor_id ARRAY,
		IN option_value_id ARRAY,
		IN related INT,
		IN variant INT,
		IN status INT,
		IN search CHAR,
		IN like CHAR,
		IN sku CHAR,
		IN barcode CHAR,
		IN upc CHAR,
		IN ean CHAR,
		IN isbn CHAR,
		IN slug ARRAY,
		IN taxonomy CHAR,
		
		-- pagination
		IN start INT,
		IN limit INT,
		IN type CHAR,
		IN order_by CHAR,
		IN direction CHAR,
		
		-- columns options (local variables used for conditional sql)
		LOCAL manufacturer INT,  -- include manufacturer
		LOCAL discount INT,      -- include discounts
		LOCAL promotion INT,     -- include promotional price
		LOCAL points INT,        -- include points
		LOCAL stock_status INT,  -- include stock_status
		LOCAL product_image INT, -- include image gallery
		LOCAL variant INT,       -- include variants
		LOCAL variant_price INT, -- include variants min max prices
		LOCAL weight_type INT,   -- include weight type
		LOCAL length_type INT,   -- include length type
		LOCAL rating INT,        -- include rating average
		LOCAL reviews INT,       -- include reviews count
		LOCAL author INT,        -- include author/admin info

			
		-- return array of products for products query
		OUT fetch_all,
		-- return products count for count query
		OUT fetch_one,
	)
	BEGIN

		SELECT  pd.*,product.*, product.product_id as array_key

				@IF !empty(:manufacturer) 
				THEN 
					,m.name AS manufacturer
				END @IF
				

			-- include author info	
			
			@IF !empty(:author) 
			THEN 
				,ad.display_name, ad.first_name, ad.last_name, ad.email, ad.last_name, ad.phone_number, ad.phone_number, ad.url, ad.avatar, ad.bio, ad.status, ad.created_at
			END @IF			
			
			-- include image gallery 	
			
			@IF !empty(:product_image) 
			THEN 
				-- ,(SELECT CONCAT('[', GROUP_CONCAT('{"id":"', pi.product_image_id, '","image":"', pi.image, '"}'), ']') 
				,(SELECT JSON_ARRAYAGG( JSON_OBJECT('id', pi.product_image_id, 'image', pi.image) ) 
				FROM product_image as pi WHERE pi.product_id = product.product_id GROUP BY pi.product_id) as images
			END @IF

			-- include discount 	
			@IF !empty(:discount) && !empty(:user_group_id) 
			THEN 
			
				 ,(SELECT price
				   FROM product_discount pd2
				   WHERE pd2.product_id = product.product_id
					 AND pd2.user_group_id = :user_group_id
					 AND pd2.quantity = '1'
					 AND ((pd2.from_date = NULL
						   OR pd2.from_date < NOW())
						  AND (pd2.to_date = NULL
							   OR pd2.to_date > NOW()))
				   ORDER BY pd2.priority ASC, pd2.price ASC
				   LIMIT 1) AS discount
				   
			END @IF
			
			-- include promotional price 	
			@IF !empty(:promotion) && !empty(:user_group_id) 
			THEN 
				,(SELECT pp.price FROM product_promotion pp 
					WHERE pp.product_id = product.product_id AND pp.user_group_id = :user_group_id 
						AND (
							(pp.from_date = NULL OR pp.from_date < NOW()) 
							AND (pp.to_date = NULL OR pp.to_date > NOW())
						) 
					ORDER BY pp.priority ASC, pp.price ASC 
					LIMIT 1
				) AS promotion				
			END @IF		

			-- include variant price 	
			@IF !empty(:variant_price)
			THEN 
				,(SELECT MIN(pvmin.price) FROM product_variant pvmin 
					WHERE pvmin.product_id = product.product_id
					LIMIT 1
				) AS min_price		
				
				,(SELECT MAX(pvmax.price) FROM product_variant pvmax 
					WHERE pvmax.product_id = product.product_id
					LIMIT 1
				) AS max_price				
			END @IF
			
			-- include points 	
			@IF !empty(:points) && !empty(:user_group_id) 
			THEN 
			
			  ,(SELECT points
			   FROM product_points pp
			   WHERE pp.product_id = product.product_id
				 AND pp.user_group_id = :user_group_id
			   AS points
			   
			END @IF
			
			-- include stock_status 	
			@IF !empty(:stock_status)
			THEN 

			  ,(SELECT ss.name
			   FROM stock_status ss
			   WHERE ss.stock_status_id = product.stock_status_id
				 AND ss.language_id = :language_id) 
			  AS stock_status

			   
			END @IF


			-- include weight_type 	
			@IF !empty(:weight_type)
			THEN 
			
			  ,(SELECT wcd.unit
			   FROM weight_type_content wcd
			   WHERE product.weight_type_id = wcd.weight_type_id
				 AND wcd.language_id = :language_id) 
			   AS weight_type
			   
			END @IF


			-- include length_type 	
			@IF !empty(:length_type)
			THEN 
			
			  ,(SELECT lcd.unit
			   FROM length_type_content lcd
			   WHERE product.length_type_id = lcd.length_type_id
				 AND lcd.language_id = :language_id) 
			   AS length_type
			   
			END @IF
		
		
			-- include rating
			@IF !empty(:rating)
			THEN 
			
			  ,(SELECT AVG(rating) AS total
			   FROM review r1
			   WHERE r1.product_id = product.product_id
				 AND r1.status = '1'
			   GROUP BY r1.product_id) 
			  AS rating

			   
			END @IF
		
			-- include reviews
			@IF !empty(:reviews)
			THEN 

			  ,(SELECT COUNT(*) AS total
			   FROM review r2
			   WHERE r2.product_id = product.product_id
				 AND r2.status = '1'
			   GROUP BY r2.product_id) AS reviews
									
			   
			END @IF
		
		
			@IF isset(:search)
			THEN 
				,MATCH(pd.name, pd.content)				
				AGAINST(
					:search
					 @IF isset(:search_boolean) && !empty(:search_boolean)
					 THEN 
						IN BOOLEAN MODE
					 END @IF	     
				) as score
			END @IF	

		 
		FROM product
		
			LEFT JOIN product_to_site p2s ON (product.product_id = p2s.product_id) 
			LEFT JOIN product_content pd ON (
				product.product_id = pd.product_id
		
				@IF isset(:language_id)
				THEN
					AND pd.language_id = :language_id
				END @IF

			)  

			@IF !empty(:manufacturer) 
			THEN 
				LEFT JOIN manufacturer m ON (product.manufacturer_id = m.manufacturer_id)
			END @IF
			
			@IF !empty(:taxonomy_item_id) 
			THEN 
				INNER JOIN product_to_taxonomy_item pt ON (product.product_id = pt.product_id AND pt.taxonomy_item_id IN (:taxonomy_item_id))
			END @IF				

			@IF isset(:taxonomy) && :taxonomy !== ""
			THEN 
				INNER JOIN product_to_taxonomy_item pt ON (product.product_id = pt.product_id)
				INNER JOIN taxonomy_item_content pic ON (pic.taxonomy_item_id = pt.taxonomy_item_id AND pic.slug = :taxonomy)
			END @IF				
			
			@IF !empty(:related) 
			THEN 
				INNER JOIN product_related pr ON (pr.product_related_id = product.product_id)
			END @IF		

			@IF !empty(:variant) 
			THEN 
				INNER JOIN product_variant pv ON (pv.product_variant_id = product.product_id)
			END @IF				
			
			@IF !empty(:option_value_id) 
			THEN 
				INNER JOIN product_option_value pov ON (pov.product_id = product.product_id)
			END @IF		

			@IF !empty(:product_attribute) AND !empty(:product_attribute_id) 
			THEN 
				INNER JOIN product_attribute pa ON (pa.product_id = product.product_id)
			END @IF		

			@IF !empty(:author) AND !empty(:author) 			
			THEN 
				LEFT JOIN admin ad ON (product.admin_id = ad.admin_id)  
			END @IF			

			WHERE p2s.site_id = :site_id

			-- search
			@IF isset(:search) && !empty(:search)
			THEN 
				-- AND pd.name LIKE CONCAT('%',:search,'%')
				AND MATCH(pd.name, pd.content)
				AGAINST(
					:search
					 @IF isset(:search_boolean) && !empty(:search_boolean)
					 THEN 
						IN BOOLEAN MODE
					 END @IF	     
				)
			END @IF     
                                
			-- like
			@IF isset(:like) && !empty(:like)
			THEN 
				 AND pd.name LIKE CONCAT('%',:like,'%')
			END @IF     
                       
					   
			@IF isset(:type) && !empty(:type)
			THEN  
				AND product.type = :type
			END @IF		
			
			@IF isset(:manufacturer_id) && !empty(:manufacturer_id)
			THEN 
				AND product.manufacturer_id IN (:manufacturer_id)
			END @IF	   		

			@IF isset(:admin_id) && !empty(:admin_id)
			THEN 
				AND product.admin_id = :admin_id
			END @IF	   		
			
			@IF isset(:vendor_id) && !empty(:vendor_id)
			THEN 
				AND product.vendor_id IN (:vendor_id)
			END @IF	    			
			
			@IF isset(:price) && :price !== ""
			THEN 
				AND product.price = :price
			END @IF	  			
			
			@IF isset(:quantity) && :quantity !== ""
			THEN 
				AND product.quantity = :quantity
			END @IF				
			
			@IF isset(:model) && :model !== ""
			THEN 
				AND product.model = :model
			END @IF				
			
			@IF isset(:sku) && :sku !== ""
			THEN 
				AND product.sku = :sku
			END @IF	 			
			
			@IF isset(:barcode) && :barcode !== ""
			THEN 
				AND product.barcode = :barcode
			END @IF	 
			
			@IF isset(:upc) && :upc !== ""
			THEN 
				AND product.upc = :upc
			END @IF	 	
			
			@IF isset(:ean) && :ean !== ""
			THEN 
				AND product.ean = :ean
			END @IF	    
			
			@IF isset(:isbn) && :isbn !== ""
			THEN 
				AND product.isbn = :isbn
			END @IF				
			
			
			@IF isset(:status) && :status !== ""
			THEN 
				AND product.status = :status
			END @IF				


			@IF isset(:product_id) && count(:product_id) > 0
			THEN 
			
				AND product.product_id IN (:product_id)
				
			END @IF				
			
			@IF !empty(:related) 
			THEN 
			
				AND pr.product_id = :related
				
			END @IF	

			@IF !empty(:variant) 
			THEN 
			
				AND pv.product_id = :variant
				
			END @IF	
			
			@IF !empty(:taxonomy_item_id) 
			THEN 
			
				AND pt.taxonomy_item_id = :taxonomy_item_id
				
			END @IF		
			
		
			@IF isset(:slug) && count(:slug) > 0
			THEN 
			
				AND pd.slug IN (:slug)
				
			END @IF				

			@IF !empty(:option_value_id) 
			THEN 
				AND pov.option_value_id IN (:option_value_id)
			END @IF		


			@IF !empty(:product_attribute_id) 
			THEN 
				pa.product_attribute_id IN (:product_attribute_id)
			END @IF		


			@IF !empty(:product_attribute)
			THEN 
				pa.text IN (:product_attribute)
			END @IF		


		-- ORDER BY parameters can't be binded, because they are added to the query directly they must be properly sanitized by only allowing a predefined set of values
		@IF isset(:order_by)
		THEN
			ORDER BY product.$order_by $direction		
		@ELSE
			ORDER BY product.product_id DESC
		END @IF
		

		@IF isset(:limit)
		THEN
			@SQL_LIMIT(:start, :limit)
		END @IF;		
		
		-- SELECT FOUND_ROWS() as count;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(product.product_id, product) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;


	END
