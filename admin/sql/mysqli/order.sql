-- Orders

	-- get all orders

	CREATE PROCEDURE getAll(
		-- variables
		IN  language_id INT,
		IN  site_id INT,
		IN 	user_id INT
		
		IN order_status CHAR,
		IN order_status_id INT,
		IN email CHAR,
		IN phone_number CHAR,
		IN search CHAR,
		
		-- pagination
		IN start INT,
		IN limit INT,
		
		
		-- return
		OUT fetch_all, -- orders
		OUT fetch_one  -- count
	)
	BEGIN
        
        SELECT `order`.*,os.name as order_status FROM `order` AS `order`
		
			LEFT JOIN order_status AS os ON (`order`.order_status_id = os.order_status_id AND os.language_id = :language_id) 
			
		WHERE 1 = 1 
		
			AND `order`.site_id = :site_id
			
			@IF isset(:user_id)
			THEN 
				AND `order`.user_id = :user_id
			END @IF
			
			@IF isset(:order_status)
			THEN 
				AND os.name = :order_status
			END @IF					
			
			@IF isset(:order_status_id)
			THEN 
				AND `order`.order_status_id = :order_status_id
			END @IF		

			@IF isset(:email) AND !empty(:email)
			THEN 
				AND `order`.email = :email 
        	END @IF	

			@IF isset(:phone_number) AND !empty(:phone_number)
			THEN 
				AND `order`.phone_number = :phone_number 
        	END @IF	

            -- search
            @IF isset(:search) AND !empty(:search)
			THEN 
				AND
				`order`.first_name LIKE CONCAT('%',:search,'%') OR
				`order`.last_name LIKE CONCAT('%',:search,'%')
        	END @IF	  
			
			ORDER BY order_id DESC
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(order_id, order) -- this takes previous query removes limit and replaces select columns with parameter order_id
			
		) as count;

    END	


	CREATE PROCEDURE get(
        IN order_id INT,
		IN user_id INT,
		IN language_id INT,
		OUT fetch_row,
		OUT fetch_all,
		OUT fetch_all,
		OUT fetch_all,
		OUT fetch_all,
		OUT fetch_all,
		OUT fetch_all,
		OUT fetch_all,
	)
	BEGIN
        
		-- order
		SELECT `order`.*,
			os.name as order_status,
			bc.name as billing_country,
			sc.name as shipping_country,
			br.name as billing_region,
			sr.name as shipping_region
			
		FROM `order`
	
			LEFT JOIN order_status AS os ON (`order`.order_status_id = os.order_status_id AND os.language_id = :language_id) 
			-- country
			LEFT JOIN country AS bc ON (`order`.billing_country_id = bc.country_id) 
			LEFT JOIN country AS sc ON (`order`.shipping_country_id = sc.country_id) 
			-- Region
			LEFT JOIN region AS br ON (`order`.billing_region_id = br.region_id) 
			LEFT JOIN region AS sr ON (`order`.shipping_region_id = sr.region_id) 
			
		WHERE  1 = 1
			
		@IF isset(:user_id)
		THEN 
			AND `order`.user_id = :user_id
		END @IF

		AND `order`.order_id = :order_id;
		
        	
		SELECT `key` as array_key,value as array_value FROM order_meta as _
			WHERE _.order_id = :order_id;
            
		-- products 
		SELECT *,products.name as name, products.product_id as array_key 
		FROM order_product as products
			LEFT JOIN product ON product.product_id = products.product_id	
			LEFT JOIN product_content ON product_content.product_id = products.product_id	
			LEFT JOIN order_product_option ON products.order_product_id = order_product_option.order_product_id	

			@IF isset(:language_id)
			THEN 
				AND product_content.language_id = :language_id
			END @IF


		WHERE products.order_id = :order_id;		
			
		-- history	
		SELECT * FROM order_log as history
			WHERE history.order_id = :order_id;		
			
		-- meta
		SELECT * FROM order_meta as meta
			WHERE meta.order_id = :order_id;
			
        -- total
		SELECT * FROM order_total as total
			WHERE total.order_id = :order_id;		
			
		-- shipment	
		SELECT * FROM order_shipment as shipment
			WHERE shipment.order_id = :order_id;		
			
		-- voucher	
		SELECT * FROM order_voucher as voucher
			WHERE voucher.order_id = :order_id;        
        
    END    
    

	PROCEDURE getData(
		IN order_id INT,
		IN language_id INT,
		IN billing_country_id INT,
		IN shipping_country_id INT,
		
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
		OUT fetch_all, 
	)
	BEGIN
	
		-- order status	
		SELECT 
		
			order_status_id as array_key, -- order_id as key
			name as array_value -- only set name as value and return  
			
		FROM order_status as order_status_id WHERE language_id = :language_id;
		
		-- billing_country
		SELECT 
			country_id as array_key, -- order_id as key
			name as array_value -- only set name as value and return  
		FROM country as billing_country_id WHERE status = 1;	

		-- shipping_country
		SELECT 
			country_id as array_key, -- order_id as key
			name as array_value -- only set name as value and return  
		FROM country as shipping_country_id WHERE status = 1;	

		-- billing_region
		SELECT 
			region_id as array_key, -- order_id as key
			name as array_value -- only set name as value and return  
		FROM region as billing_region_id
		WHERE status = 1

		@IF isset(:billing_country_id)
		THEN 
			AND billing_region_id.country_id = :billing_country_id
		END @IF;	

		-- shipping_region
		SELECT 
			region_id as array_key, -- order_id as key
			name as array_value -- only set name as value and return  
		FROM region as shipping_region_id
		WHERE status = 1

		@IF isset(:shipping_country_id)
		THEN 
			AND shipping_region_id.country_id = :shipping_country_id
		END @IF;	

	END

	-- delete order
	
	PROCEDURE delete(
		IN  order_id ARRAY,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM order_product WHERE order_id IN (:order_id);
		DELETE FROM order_product_option WHERE order_id IN (:order_id);
		DELETE FROM order_subscription WHERE order_id IN (:order_id);
		DELETE FROM order_shipment WHERE order_id IN (:order_id);
		DELETE FROM order_voucher WHERE order_id IN (:order_id);
		DELETE FROM order_total WHERE order_id IN (:order_id);
		DELETE FROM voucher WHERE order_id IN (:order_id);
		DELETE FROM voucher_log WHERE order_id IN (:order_id);
		DELETE FROM order_log WHERE order_id IN (:order_id);
		DELETE FROM `order` WHERE order_id IN (:order_id);
		
	END	
	
	
	-- add order

	CREATE PROCEDURE add(
		IN order ARRAY,
		OUT insert_id,
		OUT insert_id,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN

		:products  = @FILTER(:order.products, order_product, false, true);
		:totals  = @FILTER(:order.totals, order_total, false, true);
		@FILTER(:order, order);
		
		INSERT INTO `order` 
			
			( @KEYS(:order) )
			
	  	VALUES ( :order );
		
		-- insert order products
		@EACH(:products) 
			INSERT INTO order_product 
				( order_id, @KEYS(:each) )
			VALUES ( @result.order, :each  );
		
		-- insert product option
		@EACH(:product_options) 
			INSERT INTO order_product_option 
				( order_id, @KEYS(:each) )
			VALUES ( @result.order, 0,  :each  );

		-- insert order products
		@EACH(:totals) 
			INSERT INTO order_total 
				( order_id, @KEYS(:each) )
			VALUES ( @result.order, :each  );

    END

	-- edit order

	CREATE PROCEDURE edit(
        IN order_id INT,
		IN order ARRAY,
		OUT affected_rows
	)
	BEGIN
		
		@FILTER(:order, order);
	
		UPDATE `order` 
			
			SET @LIST(:order) 
			
		WHERE order_id = :order_id;

    END