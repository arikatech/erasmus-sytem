# Erasmus+ Project System

A simple web application for managing Erasmus+ style projects, NGOs, and participants.

This project was built as part of a university **Database Systems course** and demonstrates how to design a database using an **Entity-Relationship model**, translate it into a **relational schema**, and implement it in a web application using **PHP and MySQL**.

---

## Features

- Manage **Countries**
- Manage **NGOs**
- Manage **Participants**
- Manage **Projects**
- Define **eligible countries** for projects
- Participants can **apply to projects**
- Track **participation status** (pending / accepted / rejected)
- Store **participant roles** in projects

The system demonstrates handling **one-to-many and many-to-many relationships** in a relational database.

---

## ER Diagram
<img width="1160" height="583" alt="er-diagram" src="https://github.com/user-attachments/assets/0394d742-4c4d-4209-8daa-a98556df942e" />


---

## Relational Schema

<img width="768" height="659" alt="relational-schema" src="https://github.com/user-attachments/assets/de288741-1b49-4d35-9ea6-4ff6255f7c76" />


---

## Technologies

- PHP (procedural)
- MySQL
- mysqli library
- HTML
- XAMPP

---

## Database Concepts Demonstrated

- Entity-Relationship modeling
- Relational schema design
- Normalization
- Many-to-many relationships
- Bridge tables
- SQL joins
- Data validation

---

## Example Entities

Main tables implemented in the database:

- `ngo`
- `project`
- `participant`
- `country`
- `activity`
- `membership`
- `participation`
- `review`
- `project_eligible_country`

---
