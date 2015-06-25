package fr.obvil.geoprocessing.enrichgeoonto.old;

import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.nio.file.FileVisitResult;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.SimpleFileVisitor;
import java.nio.file.attribute.BasicFileAttributes;
import java.util.Date;
import java.util.HashSet;
import java.util.Iterator;
import java.util.List;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.core.KeywordAnalyzer;
import org.apache.lucene.analysis.standard.StandardAnalyzer;
import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;
import org.apache.lucene.document.StringField;
import org.apache.lucene.index.DirectoryReader;
import org.apache.lucene.index.IndexReader;
import org.apache.lucene.index.IndexWriter;
import org.apache.lucene.index.IndexWriterConfig;
import org.apache.lucene.index.IndexWriterConfig.OpenMode;
import org.apache.lucene.index.Term;
import org.apache.lucene.queryparser.classic.ParseException;
import org.apache.lucene.queryparser.classic.QueryParser;
import org.apache.lucene.search.ConstantScoreQuery;
import org.apache.lucene.search.Filter;
import org.apache.lucene.search.IndexSearcher;
import org.apache.lucene.search.PrefixFilter;
import org.apache.lucene.search.Query;
import org.apache.lucene.search.ScoreDoc;
import org.apache.lucene.search.TopDocs;
import org.apache.lucene.store.Directory;
import org.apache.lucene.store.FSDirectory;

import com.hp.hpl.jena.ontology.Individual;
import com.hp.hpl.jena.ontology.OntClass;
import com.hp.hpl.jena.ontology.OntModel;
import com.hp.hpl.jena.ontology.OntModelSpec;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Resource;

import au.com.bytecode.opencsv.CSVReader;
import au.com.bytecode.opencsv.CSVWriter;

/**
 * 
 * @author Carmen Brando (Labex OBVIL - Univ. Paris-Sorbonne - UPMC LIP6)
 * 
 *         Methods to create an index from reference geodata sets and to search
 *         for places within the index.
 * 
 */
public class IndexProcessing {

	/**
	 * Main method to build index on dictionary files.
	 * 
	 * @param indexDirStr
	 *            , index folder
	 * @param dataDirStr
	 *            , data
	 */
	public static void createIndex(String indexDirStr, String dataDirStr) {

		final Path docDir = Paths.get(dataDirStr);
		if (!Files.isReadable(docDir)) {
			System.out
					.println("Document directory '"
							+ docDir.toAbsolutePath()
							+ "' does not exist or is not readable, please check the path");
			System.exit(1);
		}
		System.out.println("Data dir: " + docDir);
		Date start = new Date();
		try {
			System.out
					.println("Indexing to directory '" + indexDirStr + "'...");
			Directory dir = FSDirectory.open(Paths.get(indexDirStr));
			Analyzer analyzer = new StandardAnalyzer();
			IndexWriterConfig iwc = new IndexWriterConfig(analyzer);

			iwc.setOpenMode(OpenMode.CREATE);
			// Optional: for better indexing performance, if you
			// are indexing many documents, increase the RAM
			// buffer. But if you do this, increase the max heap
			// size to the JVM (eg add -Xmx512m or -Xmx1g):
			// iwc.setRAMBufferSizeMB(256.0);

			IndexWriter writer = new IndexWriter(dir, iwc);
			indexDocs(writer, docDir);

			writer.close();
			Date end = new Date();
			System.out.println(end.getTime() - start.getTime()
					+ " total milliseconds");
		} catch (IOException e) {
			System.out.println(" caught a " + e.getClass()
					+ "\n with message: " + e.getMessage());
		}
	}

	/**
	 * Indexes documents in folder.
	 * 
	 * @param writer
	 * @param path
	 * @throws IOException
	 */
	static void indexDocs(final IndexWriter writer, Path path)
			throws IOException {
		if (Files.isDirectory(path)) {
			Files.walkFileTree(path, new SimpleFileVisitor<Path>() {
				@Override
				public FileVisitResult visitFile(Path file,
						BasicFileAttributes attrs) throws IOException {
					try {
						indexDoc(writer, file, attrs.lastModifiedTime()
								.toMillis());
					} catch (IOException ignore) {
						// don't index files that can't be read.
					}
					return FileVisitResult.CONTINUE;
				}
			});
		} else {
			indexDoc(writer, path, Files.getLastModifiedTime(path).toMillis());
		}
	}

	/**
	 * Indexes a single document.
	 * 
	 * @param writer
	 * @param file
	 * @param lastModified
	 * @throws IOException
	 */
	static void indexDoc(IndexWriter writer, Path file, long lastModified)
			throws IOException {
		try (InputStream stream = Files.newInputStream(file)) {

			CSVReader reader = new CSVReader(new InputStreamReader(
					new FileInputStream(file.toFile()), "UTF-8"), '\t',
					CSVWriter.NO_QUOTE_CHARACTER);
			String[] line = {};

			while ((line = reader.readNext()) != null) {
				// make a new, empty document
				Document doc = new Document();
				Field pathField = new StringField("nameForm",
						replaceNonAlphabeticCharacters(line[1]),
						Field.Store.YES);
				doc.add(pathField);
				String[] multiAltnames = line[3].split(",");
				for (String name : multiAltnames) {
					Field aliasesField = new StringField("AliasesName",
							replaceNonAlphabeticCharacters(name),
							Field.Store.YES);
					doc.add(aliasesField);
				}

				String lat = line[4];
				String lon = line[5];
				doc.add(new StringField("Lat", lat, Field.Store.YES));
				doc.add(new StringField("Lon", lon, Field.Store.YES));
				String fc = line[6];
				doc.add(new StringField("FeatureClass", fc, Field.Store.YES));
				String fcode = line[7];
				doc.add(new StringField("FeatureCode", fcode, Field.Store.YES));
				String id = line[0];
				doc.add(new StringField("objectID", id, Field.Store.YES));
				String continentInfo = line[17].trim();
				doc.add(new StringField("TimeZone", continentInfo, Field.Store.YES));
				writer.addDocument(doc);
			}
			System.out.println("file processed " + file);
			reader.close();
		}
	}
	
	/**
	 * Indexes place names and the corresponding articles of the ontology of Mercure Galant 
	 * 
	 * @param indexFolder, folder where to store the index
	 * @param onto, 
	 */
	static void indexOnto(String indexFolder, String onto) {
		try {
		OntModel m = ModelFactory.createOntologyModel(OntModelSpec.OWL_MEM,
				null);
		m.read(onto);
		System.out.println("Indexing to directory '" + indexFolder + "'...");
		Directory dir = FSDirectory.open(Paths.get(indexFolder));
		Analyzer analyzer = new StandardAnalyzer();
		IndexWriterConfig iwc = new IndexWriterConfig(analyzer);
		iwc.setOpenMode(OpenMode.CREATE);
		IndexWriter writer = new IndexWriter(dir, iwc);

		for (Iterator<Individual> i = m.listIndividuals(); i.hasNext();) {
			Individual ind = i.next();
			// check individual belongs to given class (ex: Article)
			Boolean is = false;
			Iterator<Resource> it = ind.listRDFTypes(true);
			while (it.hasNext() && !is) {
				Resource res = (Resource) it.next();
				OntClass ontCls = m.getOntClass(res.getURI());
				if (ontCls != null) {
					if (ontCls.getURI().split("#")[1]
							.equalsIgnoreCase("Article")) {
						is = true;
					}
				}
			}
			if (is) {
				Document doc = new Document();
				doc.add(new StringField("articleID", ind.getURI(), Field.Store.YES)); //the article
				Property prop = m.getProperty("http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#contains_place");
				int count = 0;
				List<RDFNode> l = ind.listPropertyValues(prop).toList();
				while (count < l.size()) {
					RDFNode propVal = l.get(count);
					Field pathField = new StringField("placeName",
							replaceNonAlphabeticCharacters(propVal.toString().split("#")[1]),
							Field.Store.YES);
					doc.add(pathField);
					count++;
				}
				writer.addDocument(doc);
			}
		}
		writer.close();
		} catch (IOException e) {
			System.out.println(" caught a " + e.getClass()
					+ "\n with message: " + e.getMessage());
		}
	}


	/**
	 * Method to search for a phrase in the index built from the reference geo
	 * data set.
	 * 
	 * @param index
	 *            , name of the index
	 * @param field
	 *            , name of the field to search within the index
	 * @param queryString
	 *            , the phrase to search for
	 * @return the results
	 */
	public static Set<String> searchIndex(String index, String field,
			String queryString, String filter, Integer nbResults) {
		Set<String> results = new HashSet<String>();
		// check index folder exist
		if (new File("geoIndex").exists()) {
			try {
				IndexReader reader = DirectoryReader.open(FSDirectory
						.open(Paths.get(index)));
				IndexSearcher searcher = new IndexSearcher(reader);
				Date start = new Date();
				TopDocs hits = null;
				if (filter == null) {
					Analyzer analyzer = new KeywordAnalyzer();
					QueryParser parser = new QueryParser(field, analyzer);
					parser.setDefaultOperator(QueryParser.Operator.AND);
					Query query = parser.parse(queryString);
					//System.out.println("Searching for: " + query.toString());
					hits = searcher.search(query, nbResults);
				} else {
					//System.out.println("Searching for: " + filter);
					Filter prefixFilter = new PrefixFilter(new Term(field,
							replaceNonAlphabeticCharacters(filter)));
					Query query2 = new ConstantScoreQuery(prefixFilter);
					hits = searcher.search(query2, prefixFilter, nbResults);
				}
				Date end = new Date();
				//System.out.println("Time: " + (end.getTime() - start.getTime())+ "ms");
				ScoreDoc[] scoreDocs = hits.scoreDocs;
				for (int n = 0; n < scoreDocs.length; ++n) {
					ScoreDoc sd = scoreDocs[n];
					int docId = sd.doc;
					Document d = searcher.doc(docId);
					// only administrative places
					if (d.get("FeatureClass").equals("P")
							|| d.get("FeatureClass").equals("A")) {
						
						results.add(d.get("nameForm") + "|" + d.get("objectID")
								+ "|" + d.get("Lat") + "|" + d.get("Lon") + "|"
								+ d.get("FeatureClass")+"|"+d.get("TimeZone")+"|"+d.get("FeatureCode"));
						/* System.out.println("Name: "+d.get("nameForm"));
						 System.out.println("Lat found: "+d.get("Lat"));
						 System.out.println("Lon found: "+d.get("Lon"));
						 System.out.println("ObjID found: "+d.get("objectID"));
						 System.out.println("Feature class found: "+d.get("FeatureClass"));*/
						 
					}
				}
				reader.close();
			} catch (IOException e) {
				e.printStackTrace();
			} catch (ParseException e) {
				e.printStackTrace();
			}
		} else {
			System.out.println("Index folder doesn't exist");
		}
		return results;
	}
	
	/**
	 * Method to search for article identifier in the index given a place name
	 * data set.
	 * 
	 * @param index
	 *            , name of the index
	 * @param field
	 *            , name of the field to search within the index
	 * @param queryString
	 *            , the phrase to search for
	 * @return the results
	 */
	public static Set<String> searchOnto(String index, String field,
			String queryString) {
		Set<String> results = new HashSet<String>();
		// check index folder exist
		if (new File(index).exists()) {
			try {
				IndexReader reader = DirectoryReader.open(FSDirectory
						.open(Paths.get(index)));
				IndexSearcher searcher = new IndexSearcher(reader);
				Date start = new Date();
				Analyzer analyzer = new KeywordAnalyzer();
				QueryParser parser = new QueryParser(field, analyzer);
				parser.setDefaultOperator(QueryParser.Operator.AND);
				Query query = parser.parse(queryString);
				System.out.println("Searching for: " + query.toString());
				TopDocs hits = searcher.search(query, 100);
				Date end = new Date();
				//System.out.println("Time: " + (end.getTime() - start.getTime())+ "ms");
				ScoreDoc[] scoreDocs = hits.scoreDocs;
				for (int n = 0; n < scoreDocs.length; ++n) {
					ScoreDoc sd = scoreDocs[n];
					int docId = sd.doc;
					Document d = searcher.doc(docId);
					results.add(d.get("articleID"));
					System.out.println("art id: "+d.get("articleID"));
				}
				reader.close();
			} catch (IOException e) {
				e.printStackTrace();
			} catch (ParseException e) {
				e.printStackTrace();
			}
		} else {
			System.out.println("Onto index folder doesn't exist");
		}
		return results;
	}

	/**
	 * Remove special characters and spaces in mentions if necessary.
	 * 
	 * @param in
	 *            , the string
	 * @return the new string
	 */
	public static String replaceNonAlphabeticCharacters(String in) {
		Pattern p = Pattern.compile("\\s|'|-|_");
		Matcher m = p.matcher(in);
		String texteRemplace = m.replaceAll("");
		return texteRemplace.toLowerCase().trim();
	}

	public static void main(String[] args) {
		// createIndex("geoIndex", "geonames");
		/*Set<String> results = IndexProcessing.searchIndex("geoIndex",
				"nameForm", replaceNonAlphabeticCharacters("angleterre"), null);
		for (String r : results) {
			System.out.println(r);
		}
		if (results.size() == 0) {
			Set<String> results2 = IndexProcessing.searchIndex("geoIndex",
					"AliasesName", null,
					replaceNonAlphabeticCharacters("angleterre"));
			for (String r : results2) {
				System.out.println(r);
			}
		}*/
		//indexOnto("ontoIndex", "ontology/root-ontology.owl");
		searchOnto("ontoIndex", "placeName", replaceNonAlphabeticCharacters("PARIS,_Théâtre_du_Marais"));
	}

}
