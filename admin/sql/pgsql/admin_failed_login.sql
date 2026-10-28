-- admin_failed_login

	-- get all entries

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (admin_id) for cursor pagination
		IN admin_id INT,
		IN count INT,
		IN updated_at CHAR,
		
		-- return array of admin_failed_login 
		OUT fetch_all,
		OUT fetch_one
	)
	BEGIN
        
        SELECT * FROM admin_failed_login WHERE 1 = 1 

        	@IF isset(:admin_id) AND !empty(:admin_id)
			THEN 
				AND admin_failed_login.admin_id = :admin_id 
        	END @IF	

			@IF isset(:count) AND !empty(:count)
			THEN 
				AND admin_failed_login.count > :count
        	END @IF				
			
			@IF isset(:updated_at) AND !empty(:updated_at)
			THEN 
				AND admin_failed_login.updated_at = :updated_at
        	END @IF	

			-- cursor pagination

			@IF isset(:cursor)

			THEN

			    AND admin_failed_login.admin_id > :cursor

			END @IF

			ORDER BY admin_failed_login.admin_id, admin_failed_login.updated_at
			
			-- limit
			@IF isset(:limit)
			THEN
				LIMIT :limit OFFSET :start
			END @IF;		

		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(admin_failed_login.admin_id, admin) -- this takes previous query removes limit and replaces select columns with parameter admin_id
			
		) as count;				
        
    END

	-- get admin information

	CREATE PROCEDURE get(
		IN admin_id INT,
		IN updated_at CHAR,
		IN count INT,
		IN username CHAR,
		IN email CHAR,
		IN status INT,
		IN role_id INT,
		
		OUT fetch_row
	)
	BEGIN
        
        SELECT _.* FROM admin_failed_login AS _ 
			LEFT JOIN admin ON (admin.admin_id = _.admin_id)
		
		WHERE 1 = 1


		@IF isset(:admin_id) AND !empty(:admin_id)
		THEN 
			AND _.admin_id = :admin_id 
		END @IF	

		@IF isset(:count) AND !empty(:count)
		THEN 
			AND _.count > :count
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
    
    

	-- Add new failed attempt

	CREATE PROCEDURE logFailed(
		IN admin_id INT,
		IN username CHAR,
		IN updated_at CHAR,
		IN last_ip INT,
		IN email CHAR,
		OUT fetch_one,
		OUT affected_rows
	)
	BEGIN
	
		SELECT admin_id FROM admin WHERE admin.status = 1

		@IF isset(:admin_id) AND !empty(:admin_id)
		THEN 
			AND admin_id = :admin_id 
		END @IF	

		@IF isset(:username)
		THEN 
			AND username = :username 
		END @IF	

		@IF isset(:email)
		THEN 
			AND email = :email 
		END @IF			

		@IF isset(:admin_id)
		THEN 
			AND admin_id = :admin_id 
		END @IF			

		LIMIT 1;	
		
		@IF isset(@result.admin)
		THEN 
		
			INSERT INTO admin_failed_login 
				
				( "admin_id", "updated_at", "last_ip")
				
			VALUES ( @result.admin, :updated_at, :last_ip )	 
			
			ON CONFLICT ("admin_id", "updated_at") DO UPDATE SET count = admin_failed_login.count + 1, last_ip = :last_ip
		
		END @IF;			
	END   	
	
	-- Add new admin_failed_login

	CREATE PROCEDURE add(
		IN admin_failed_login ROW,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:admin_failed_login, admin_failed_login)
		
		INSERT INTO admin_failed_login 
			
			( @KEYS(:admin_failed_login) )
			
	  	VALUES ( :admin_failed_login )
		RETURNING admin_failed_login_id;
	END    
    

	-- Update admin_failed_login 
	
	CREATE PROCEDURE edit(
		IN admin_id INT,
		IN admin_failed_login ROW,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:admin_failed_login, admin_failed_login)

		@IF !empty(:admin_failed_login)
		THEN
		UPDATE admin_failed_login 
			
			SET @LIST(:admin_failed_login) 
			
		WHERE admin_id = :admin_id
		END @IF;

	END

	-- delete admin_failed_login

	CREATE PROCEDURE delete(
		IN admin_id ARRAY,

		OUT affected_rows
	)
	BEGIN

		DELETE FROM admin_failed_login
		WHERE 1 = 1

		@IF isset(:admin_id) AND !empty(:admin_id)
		THEN
			AND admin_failed_login.admin_id IN (:admin_id)
		END @IF

	END
