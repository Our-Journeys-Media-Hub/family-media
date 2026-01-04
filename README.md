
Family Hub – Setup Guide
Family Hub is a private web platform for securely managing and sharing family photos and videos with country and map-based organization.
Requirements
•	Server or local machine:
o	Node.js (LTS recommended)
o	MySQL or MariaDB
•	Git
•	Modern web browser
Installation
1.	Clone the repository
git clone <REPO-URL>
cd family-hub
2.	Install dependencies
npm install
3.	Configure environment variables
Create a .env file in the root directory:
PORT=3000
DB_HOST=localhost
DB_USER=youruser
DB_PASSWORD=yourpassword
DB_NAME=familyhub
JWT_SECRET=your_secret_key
4.	Prepare the database
•	Create a MySQL database
•	Import the SQL schema from 
5.	Start the server
npm run dev
or
npm start
6.	Access the app
•	Open in your browser:
http://localhost:3000
First Steps
•	Create an admin account
•	Invite family members
•	Add countries
•	Upload photos and videos
•	Browse content via map or filters
Security
•	JWT authentication
•	HTTP-only cookies
•	Access limited to invited users
•	Optional VPN access for external connections
Optional
•	Docker deployment
•	Nginx reverse proxy
•	HTTPS with Let’s Encrypt
•	NAS-based backups
License
Private use only – for family and invited members.

](https://github.com/Our-Journeys-Media-Hub/family-media.git 

Family Hub – Setup Guide
Family Hub is a private web platform for securely managing and sharing family photos and videos with country and map-based organization.
Requirements
•	Server or local machine:
o	Node.js (LTS recommended)
o	MySQL or MariaDB
•	Git
•	Modern web browser
Installation
1.	Clone the repository
git clone <REPO-URL>
cd family-hub
2.	Install dependencies
npm install
3.	Configure environment variables
Create a .env file in the root directory:
PORT=3000
DB_HOST=localhost
DB_USER=youruser
DB_PASSWORD=yourpassword
DB_NAME=familyhub
JWT_SECRET=your_secret_key
4.	Prepare the database
•	Create a MySQL database
•	Import the SQL schema from 
5.	Start the server
npm run dev
or
npm start
6.	Access the app
•	Open in your browser:
http://localhost:3000
First Steps
•	Create an admin account
•	Invite family members
•	Add countries
•	Upload photos and videos
•	Browse content via map or filters
Security
•	JWT authentication
•	HTTP-only cookies
•	Access limited to invited users
•	Optional VPN access for external connections
Optional
•	Docker deployment
•	Nginx reverse proxy
•	HTTPS with Let’s Encrypt
•	NAS-based backups
License
Private use only – for family and invited members.

)
