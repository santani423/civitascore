export const student = {
  name: 'Andi Pratama',
  firstName: 'Andi',
  studentId: '202612345',
  faculty: 'Faculty of Engineering',
  facultyId: 'engineering',
  program: 'Information Systems',
  programId: 'information-systems',
  semester: 5,
  gpa: 3.72,
  creditsEarned: 92,
  creditsRequired: 144,
  email: 'andi.pratama@student.civitas.ac.id',
  phone: '+62 812-3456-7890',
  entryYear: 2024,
  class: 'IS-A 2024',
  advisorId: 'lec-1',
  expectedGraduation: 'Aug 2028',
  birth: 'Jakarta, 14 March 2006',
  gender: 'Male',
  nationality: 'Indonesia',
  address: 'Jl. Jenderal Sudirman No. 51, Setiabudi, Jakarta Selatan 12930',
  emergency: 'Ratna Pratama (Mother) · +62 811-2233-4455',
}

export interface Lecturer {
  id: string
  name: string
  title: string
  email: string
  expertise: string
  facultyId: string
}

export const lecturers: Lecturer[] = [
  { id: 'lec-1', name: 'Dr. Maria Wijaya, M.Kom.', title: 'Associate Professor', email: 'maria.wijaya@civitas.ac.id', expertise: 'Database systems, data engineering', facultyId: 'engineering' },
  { id: 'lec-2', name: 'Yohanes Santoso, S.E., M.M.', title: 'Lecturer', email: 'yohanes.santoso@civitas.ac.id', expertise: 'Business & professional communication', facultyId: 'business' },
  { id: 'lec-3', name: 'Dr. Ir. Bernadus Hartono', title: 'Associate Professor', email: 'bernadus.hartono@civitas.ac.id', expertise: 'Software engineering, agile methods', facultyId: 'engineering' },
  { id: 'lec-4', name: 'Dr. Clara Anindita, M.Sc.', title: 'Assistant Professor', email: 'clara.anindita@civitas.ac.id', expertise: 'Human-computer interaction, UX research', facultyId: 'engineering' },
  { id: 'lec-5', name: 'Dr. Kevin Halim, M.T.', title: 'Assistant Professor', email: 'kevin.halim@civitas.ac.id', expertise: 'Data analytics, machine learning', facultyId: 'engineering' },
  { id: 'lec-6', name: 'Prof. Dr. Lucia Kristanti', title: 'Professor', email: 'lucia.kristanti@civitas.ac.id', expertise: 'Enterprise architecture, IT governance', facultyId: 'engineering' },
  { id: 'lec-7', name: 'Dr. Stefanus Gunawan, CISSP', title: 'Assistant Professor', email: 'stefanus.gunawan@civitas.ac.id', expertise: 'Information security, risk management', facultyId: 'engineering' },
  { id: 'lec-8', name: 'Dr. Theresia Paramitha', title: 'Lecturer', email: 'theresia.paramitha@civitas.ac.id', expertise: 'Applied ethics, philosophy of technology', facultyId: 'education' },
  { id: 'lec-9', name: 'Prof. Dr. Hendra Kusuma', title: 'Professor', email: 'hendra.kusuma@civitas.ac.id', expertise: 'Industrial engineering, operations research', facultyId: 'engineering' },
  { id: 'lec-10', name: 'dr. Natalia Susanto, Sp.PD', title: 'Associate Professor', email: 'natalia.susanto@civitas.ac.id', expertise: 'Internal medicine', facultyId: 'medicine' },
  { id: 'lec-11', name: 'Dr. Ignatius Wibowo, S.H., LL.M.', title: 'Associate Professor', email: 'ignatius.wibowo@civitas.ac.id', expertise: 'International trade law', facultyId: 'law' },
  { id: 'lec-12', name: 'Dr. Felicia Tanoto, M.Psi.', title: 'Assistant Professor', email: 'felicia.tanoto@civitas.ac.id', expertise: 'Clinical & educational psychology', facultyId: 'psychology' },
]

export const lecturerById = (id: string) => lecturers.find((l) => l.id === id)
