-- Addresss

	-- get all user addresses 

	CREATE PROCEDURE getAll(
		-- variables
		IN site_id INT,
        IN user_id INT,

		-- pagination
		IN start INT,
		IN limit INT,

		-- return
		OUT fetch_all, -- orders
		OUT fetch_one  -- count
	)
	BEGIN

		SELECT *
		
			FROM user_address AS user_address
		WHERE 1 = 1
            
		-- user
		@IF isset(:user_id)
		THEN 
			AND user_address.user_id  = :user_id
		END @IF	              
            

		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(user_address_id, user_address) -- this takes previous query removes limit and replaces select columns with parameter user_address_id
			
		) as count;	 
	END
	

	-- get one user_address

	CREATE PROCEDURE get(
		IN user_address_id INT,
		OUT fetch_row,
	)
	BEGIN

		SELECT * 
			FROM user_address AS _
		WHERE 1 = 1

            @IF isset(:user_address_id)
			THEN
                AND _.user_address_id = :user_address_id
        	END @IF			

        LIMIT 1; 
		
		
		-- SELECT `key` as array_key,value as array_value FROM user_address_meta as _
			-- WHERE _.user_address_id = @result.user_address_id
		
          
	END

	-- Add new user address

	CREATE PROCEDURE add(
		IN user_address ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:user_address, user_address);
		
		INSERT INTO user_address 
			
			( @KEYS(:user_address) )
			
	  	VALUES ( :user_address )
        
	END

	-- Edit user address

	CREATE PROCEDURE edit(
		IN user_address ARRAY,
		IN id_user_address INT,
        IN user_id INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:user_address, user_address);

		UPDATE user_address 
			
			SET  @LIST(:user_address) 
			
		WHERE user_address_id = :user_address_id
		
		@IF isset(:user_id)
		THEN
			AND user_id = :user_id
		END @IF;	 
		
	END
	
	-- Delete user address

	CREATE PROCEDURE delete(
		IN  user_address_id ARRAY,
		IN user_id INT,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM user_address WHERE user_address_id IN (:user_address_id) 
		
		@IF isset(:user_id)
		THEN
			AND user_id = :user_id
		END @IF;	 
	END
