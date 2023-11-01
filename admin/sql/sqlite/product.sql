-- Products

	-- get one product

	PROCEDURE get(
		IN product_id INT,
		IN slug CHAR,
		IN language_id INT,
		OUT fetch_row, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all 
	)
	BEGIN
		-- product
		SELECT pc.*,_.*, 
			mf.slug as manufacturer_slug, mf.name as manufacturer_name,
			vd.slug as vendor_slug, vd.name as vendor_name,
			st.name as stock_status_name
			
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

            @IF isset(:slug)
			THEN 
				AND pc.slug = :slug 
        	END @IF			

            @IF isset(:product_id)
			THEN 
                AND _.product_id = :product_id
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
		SELECT product_variant.product_variant_id,product_variant.product_id, pc.name,pc.slug, product_variant_id as id -- , product_image_id as product_variant_id -- product_image_id will be used as key
			FROM product_variant
			LEFT JOIN product_content pc ON (
				product_variant.product_variant_id = pc.product_id
		
				@IF isset(:language_id)
				THEN
					AND pc.language_id = :language_id
				END @IF
			)	
			
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
			
		FROM user_group as user_group
		INNER JOIN user_group_content ON user_group_content.user_group_id = user_group.user_group_id;	
		
		-- subscription plan	
		SELECT subscription_plan.subscription_plan_id as array_key,
				subscription_plan_content.name as array_value
			FROM subscription_plan AS subscription_plan
			INNER JOIN subscription_plan_content ON subscription_plan_content.subscription_plan_id = subscription_plan.subscription_plan_id 
												 AND subscription_plan_content.language_id = :language_id 
		-- LIMIT 100
		;		

		SELECT 
			`option`.option_id as array_key, -- option_id as key
			option_content.name,
			`option`.type
			FROM `option` AS `option`
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
		IN product_id INT,
		OUT insert_id
		OUT affected_rows
		OUT affected_rows
		OUT insert_id
	)
	BEGIN
		:product.product_content  = @FILTER(:product.product_content, product_content, false);
		
		@EACH(:product.product_content) 
			INSERT INTO product_content 
		
				( @KEYS(:each), product_id, meta_title, meta_description, meta_keywords )
			
			VALUES ( :each, :product_id, '', '', '' )

			ON CONFLICT("product_id", "language_id") DO UPDATE SET @LIST(:each);


		DELETE FROM product_to_taxonomy_item WHERE product_id = :product_id;

		@EACH(:product.taxonomy_item) 
			INSERT INTO product_to_taxonomy_item 
		
				( taxonomy_item_id, product_id)
			
			VALUES ( :each, :product_id);
			-- ON CONFLICT("taxonomy_item_id", "product_id") DO UPDATE SET "taxonomy_item_id" = :each;

			-- SELECT * FROM product_option WHERE product_id = :product_id;
		

		-- SELECT * FROM product_option WHERE product_id = :product_id;

		-- allow only table fields and set defaults for missing values
		:product_update  = @FILTER(:product, product, false);

		
		UPDATE product 
			
			SET @LIST(:product_update) 
			
		WHERE product_id = :product_id
		
	END	


-- Add new product

	CREATE PROCEDURE add(
		IN product ARRAY,
		OUT insert_id,
		OUT insert_id
		OUT insert_id
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:product_data  = @FILTER(:product, product);
		
		INSERT INTO product 
		
			( @KEYS(:product_data) )
			
		VALUES ( :product_data );
			

		:product_content = @FILTER(:product.product_content, product_content, false, true)

		@EACH(:product_content) 
			INSERT INTO product_content 
		
				( @KEYS(:each), product_id, meta_title, meta_description, meta_keywords )
			
			VALUES ( :each, @result.product, '', '', '' );
		
		@EACH(:product_data.taxonomy_item) 
			INSERT INTO product_to_taxonomy_item 
		
				( taxonomy_item_id, product_id)
			
			VALUES ( :each, @result.product_data)
			ON CONFLICT("product_id","taxonomy_item_id") DO UPDATE SET "taxonomy_item_id" = :each;
		
		-- UPDATE product SET image = :image WHERE product_id = :product_id;
		
		-- :product  = @FILTER(:product_data, product);
		
		INSERT INTO product_to_site 
		
			( product_id, site_id )
			
		VALUES ( @result.product, :site_id );
	 
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

		-- ON CONFLICT("image", "product_id") DO UPDATE SET "image" = :each;
		
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

			-- ON CONFLICT("product_related_id", "product_id") DO UPDATE SET "product_related_id" = :each;
		
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
		
				( product_variant_id, product_id)
			
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
		IN product_id ARRAY,
		IN taxonomy_item_id INT,
		IN manufacturer_id INT,
		IN vendor_id INT,
		IN related INT,
		IN variant INT,
		IN status INT,
		IN search CHAR,
		IN slug ARRAY,
		
		-- pagination
		IN start INT,
		IN limit INT,
		IN type CHAR,
		IN order_by CHAR,
		IN direction CHAR,
		
		-- columns options (local variables used for conditional sql)
		LOCAL include_manufacturer INT,
		LOCAL include_discount INT,
		LOCAL include_special INT,
		LOCAL include_points INT,
		LOCAL include_stock_status INT,
		LOCAL include_image_gallery INT,
			
		-- return array of products for products query
		OUT fetch_all,
		-- return products count for count query
		OUT fetch_one,
	)
	BEGIN

		SELECT  pd.*,products.*

				@IF !empty(:include_manufacturer) 
				THEN 
					,m.name AS manufacturer
				END @IF
				

			-- include image gallery 	
			
			@IF !empty(:include_image_gallery) 
			THEN
				-- uncomment the group concat version if you are using an older sqlite version
				-- ,(SELECT '[' || GROUP_CONCAT('{"id":"' || pi.product_image_id || '","image":"' || pi.image || '"}') || ']' FROM product_image as pi WHERE pi.product_id = products.product_id GROUP BY pi.product_id) as images
				,(SELECT json_group_array(json_object('id',pi.product_image_id,'image',pi.image)) FROM product_image as pi WHERE pi.product_id = products.product_id GROUP BY pi.product_id) as images
			END @IF

			-- include discount 	
			@IF !empty(:include_discount) && !empty(:user_group_id) 
			THEN 
			
				 ,(SELECT price
				   FROM product_discount pd2
				   WHERE pd2.product_id = products.product_id
					 AND pd2.user_group_id = :user_group_id
					 AND pd2.quantity = '1'
					 AND ((pd2.from_date = '0000-00-00'
						   OR pd2.from_date < NOW())
						  AND (pd2.to_date = '0000-00-00'
							   OR pd2.to_date > NOW()))
				   ORDER BY pd2.priority ASC, pd2.price ASC
				   LIMIT 1) AS discount
				   
			END @IF
			
			-- include special price 	
			@IF !empty(:include_special) && !empty(:user_group_id) 
			THEN 
			
			  ,(SELECT price
			   FROM product_promotion ps
			   WHERE ps.product_id = products.product_id
				 AND ps.user_group_id = :user_group_id
				 AND ((ps.from_date = '0000-00-00'
					   OR ps.from_date < NOW())
					  AND (ps.to_date = '0000-00-00'
						   OR ps.to_date > NOW()))
			   ORDER BY ps.priority ASC, ps.price ASC
			   LIMIT 1) AS special
			   
			END @IF


			-- include points 	
			@IF !empty(:include_points) && !empty(:user_group_id) 
			THEN 
			
			  ,(SELECT points
			   FROM product_points pp
			   WHERE pp.product_id = products.product_id
				 AND pp.user_group_id = :user_group_id
			   AS points
			   
			END @IF
			
			-- include stock_status 	
			@IF !empty(:include_stock_status)
			THEN 

			  ,(SELECT ss.name
			   FROM stock_status ss
			   WHERE ss.stock_status_id = products.stock_status_id
				 AND ss.language_id = :language_id) 
			  AS stock_status

			   
			END @IF


			-- include weight_type 	
			@IF !empty(:include_weight_type)
			THEN 
			
			  ,(SELECT wcd.unit
			   FROM weight_type_content wcd
			   WHERE products.weight_type_id = wcd.weight_type_id
				 AND wcd.language_id = :language_id) 
			   AS weight_type
			   
			END @IF


			-- include length_type 	
			@IF !empty(:include_length_type)
			THEN 
			
			  ,(SELECT lcd.unit
			   FROM length_type_content lcd
			   WHERE products.length_type_id = lcd.length_type_id
				 AND lcd.language_id = :language_id) 
			   AS length_type
			   
			END @IF
		
		
			-- include rating
			@IF !empty(:include_rating)
			THEN 
			
			  ,(SELECT AVG(rating) AS total
			   FROM review r1
			   WHERE r1.product_id = products.product_id
				 AND r1.status = '1'
			   GROUP BY r1.product_id) 
			  AS rating

			   
			END @IF
		
			-- include reviews
			@IF !empty(:include_reviews)
			THEN 

			  ,(SELECT COUNT(*) AS total
			   FROM review r2
			   WHERE r2.product_id = products.product_id
				 AND r2.status = '1'
			   GROUP BY r2.product_id) AS reviews
									
			   
			END @IF
		
		FROM product AS products
		
			LEFT JOIN product_to_site p2s ON (products.product_id = p2s.product_id) 
			LEFT JOIN product_content pd ON (
				products.product_id = pd.product_id
		
				@IF isset(:language_id)
				THEN
					AND pd.language_id = :language_id
				END @IF

			)  

			@IF !empty(:include_manufacturer) 
			THEN 
				LEFT JOIN manufacturer m ON (products.manufacturer_id = m.manufacturer_id)
			END @IF
			
			@IF !empty(:taxonomy_item_id) 
			THEN 
				INNER JOIN product_to_taxonomy_item pt ON (products.product_id = pt.product_id AND pt.taxonomy_item_id = :taxonomy_item_id)
			END @IF		

			@IF !empty(:related) 
			THEN 
				INNER JOIN product_related pr ON (pr.product_related_id = products.product_id)
			END @IF		
			
			@IF !empty(:variant) 
			THEN 
				INNER JOIN product_variant pv ON (pv.product_variant_id = products.product_id)
			END @IF		
			
			
			@IF isset(:search)
			THEN 
				JOIN product_content_search pcs ON (pcs.ROWID = pd.ROWID)   
			END @IF	

			
			WHERE p2s.site_id = :site_id

            -- search
            @IF isset(:search) && !empty(:search)
			THEN 
				-- AND pd.name LIKE CONCAT('%',:search,'%')
				-- AND MATCH(pd.name, pd.content) AGAINST(:search)
				-- AND (pcs.name MATCH :search OR pcs.content MATCH :search) 
				AND (product_content_search MATCH :search) 
        	END @IF     
                       
					   
			@IF isset(:type) && !empty(:type)
			THEN  
				AND products.type = :type
        	END @IF		
			
			@IF isset(:manufacturer_id) && !empty(:manufacturer_id)
			THEN 
				AND products.manufacturer_id = :manufacturer_id
        	END @IF	   		
			
			@IF isset(:vendor_id) && !empty(:vendor_id)
			THEN 
				AND products.vendor_id = :vendor_id
        	END @IF	    			
			
			@IF isset(:price) && :price !== ""
			THEN 
				AND products.price = :price
        	END @IF	  			
			
			@IF isset(:quantity) && :quantity !== ""
			THEN 
				AND products.quantity = :quantity
        	END @IF				
			
			@IF isset(:model) && :model !== ""
			THEN 
				AND products.model = :model
        	END @IF				
			
			@IF isset(:sku) && :sku !== ""
			THEN 
				AND products.sku = :sku
        	END @IF	 
			
			@IF isset(:upc) && :upc !== ""
			THEN 
				AND products.upc = :upc
        	END @IF	 	
			
			@IF isset(:ean) && :ean !== ""
			THEN 
				AND products.ean = :ean
        	END @IF	    
			
			@IF isset(:isbn) && :isbn !== ""
			THEN 
				AND products.isbn = :isbn
        	END @IF				
			
			
			@IF isset(:status) && :status !== ""
			THEN 
				AND products.status = :status
        	END @IF				
 
            
			@IF isset(:product_id) && count(:product_id) > 0
			THEN 
			
				AND products.product_id IN (:product_id)
				
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

		
		-- ORDER BY parameters can't be binded, because they are added to the query directly they must be properly sanitized by only allowing a predefined set of values
		@IF isset(:order_by)
		THEN
			ORDER BY products.$order_by $direction		
		@ELSE
			ORDER BY products.product_id DESC
		END @IF		
		
		
		@IF isset(:limit)
		THEN
			@SQL_LIMIT(:start, :limit)
		END @IF;		
		
		-- SELECT FOUND_ROWS() as count;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(products.product_id, product) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;


	END
