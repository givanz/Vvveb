-- admin_auth_token

	-- get all entries

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN admin_id INT,
		IN updated_at CHAR,
		
		-- return array of admin_auth_token 
		OUT fetch_all,
		-- return admin_auth_token count for count query
		OUT fetch_one
	)
	BEGIN
        
        SELECT * FROM admin_auth_token WHERE 1 = 1 

        	@IF isset(:admin_id) AND !empty(:admin_id)
			THEN 
				AND admin_auth_token.admin_id = :admin_id 
        	END @IF	

			@IF isset(:updated_at) AND !empty(:updated_at)
			THEN 
				AND admin_auth_token.updated_at = :updated_at
        	END @IF	

			ORDER BY admin_auth_token.admin_id, admin_auth_token.updated_at
			
			-- limit
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;		

		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(admin_auth_token.admin_id, admin) -- this takes previous query removes limit and replaces select columns with parameter admin_id
			
		) as count;				
        
    END

	-- get admin information

	CREATE PROCEDURE get(
		IN admin_id INT,
		IN updated_at CHAR,
		IN username CHAR,
		IN email CHAR,
		IN status INT,
		IN role_id INT,
		
		OUT fetch_row
	)
	BEGIN
        
        SELECT _.* FROM admin_auth_token AS _ 
			LEFT JOIN admin ON (admin.admin_id = _.admin_id)
		
		WHERE 1 = 1


		@IF isset(:admin_id) AND !empty(:admin_id)
		THEN 
			AND _.admin_id = :admin_id 
		END @IF	

		@IF isset(:updated_at) AND !empty(:updated_at)
		THEN 
			AND _.updated_at = :updated_at
		END @IF	
		
		@IF isset(:username)
		THEN 
			AND admin.username = :username 
		END @IF	

		@IF isset(:email)
		THEN 
			AND admin.email = :email 
		END @IF			

		@IF isset(:status)
		THEN 
			AND admin.status = :status 
		END @IF	            
			
		@IF isset(:role_id)
		THEN 
			AND admin.role_id = :role_id 
		END @IF		
		
		LIMIT 1;
        
    END
	
	-- Add new admin_auth_token

	CREATE PROCEDURE add(
		IN admin_auth_token ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:admin_auth_token, admin_auth_token)
		
		INSERT INTO admin_auth_token 
			
			( @KEYS(:admin_auth_token) )
			
	  	VALUES ( :admin_auth_token )	 
	END    
    

	-- Update admin_auth_token 
	
	CREATE PROCEDURE edit(
		IN admin_id INT,
		IN admin_auth_token ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:admin_auth_token, admin_auth_token)

		UPDATE admin_auth_token 
			
			SET @LIST(:admin_auth_token) 
			
		WHERE admin_id = :admin_id;

	END
	
	CREATE PROCEDURE update(
		IN admin_id INT,
		IN admin_auth_token ARRAY,
		OUT affected_rows
	)
	BEGIN

		@EACH(:admin_auth_token) 
			INSERT INTO admin_auth_token 
		
				( @KEYS(:each), admin_id )
			
			VALUES ( :each, :admin_id)
			ON DUPLICATE KEY UPDATE token = :each.token, description = :each.description;		

	END

	-- delete admin_auth_token

	PROCEDURE delete(
		IN admin_id INT,
		IN admin_auth_token_id ARRAY,
		IN updated_at CHAR,
		IN count INT,

		OUT affected_rows
	)
	BEGIN

		DELETE admin_auth_token FROM admin_auth_token 
			INNER JOIN admin ON admin.admin_id = admin_auth_token.admin_id
		WHERE 1 = 1

		@IF isset(:admin_id) AND !empty(:admin_id)
		THEN 
			AND admin.admin_id = :admin_id 
		END @IF	

		@IF isset(:username)
		THEN 
			AND admin.username = :username 
		END @IF	

		@IF isset(:email)
		THEN 
			AND admin.email = :email 
		END @IF			

		@IF isset(:admin_auth_token_id)
		THEN 
			AND admin_auth_token.admin_auth_token_id IN (:admin_auth_token_id)
		END @IF	
		
	END	
