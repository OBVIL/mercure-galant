package fr.obvil.geoprocessing.util;

import java.util.ArrayList;
import java.util.List;

import javax.xml.bind.JAXBContext;
import javax.xml.bind.JAXBException;
import javax.xml.bind.Unmarshaller;
import javax.xml.transform.stream.StreamSource;

import org.geonames.Toponym;

import fr.obvil.geoprocessing.enrichgeoonto.old.GeolocalisePlace;

/**
 * @author Carmen Brando
 */
public class App 
{
	@SuppressWarnings("unchecked")
	public static void main( String[] args ) {
		try {

			StreamSource xml = new StreamSource("loadUTF8.xml");
	        JAXBContext jaxbContext = JAXBContext.newInstance(Notice.class, Notices.class);
			Unmarshaller jaxbUnmarshaller = jaxbContext.createUnmarshaller();
			Notices<Notice> notices = (Notices<Notice>) jaxbUnmarshaller.unmarshal(xml,
	                Notices.class).getValue();
			List<Notice> notices2 = notices.getItems();
			int countPlace = 0;
			List<String> placeNames = new ArrayList<String>();
			for (Notice n : notices2) {
				if (n.getLc() != null) {
					for(int k = 0; k < n.getLc().size(); k++) {
						if (!placeNames.contains(n.getLc().get(k))) {
							placeNames.add(n.getLc().get(k));
						}
					}
				}
			}
			List<Toponym> toponyms = GeolocalisePlace.loadGeoCoordinatesFromGeonames(placeNames);
			for (Toponym top : toponyms) {
				System.out.println("Lieu: "+top.getName()+", Lat: "+top.getLatitude()+", Lon: "+top.getLongitude());
				countPlace++;
			}
			System.out.println(countPlace);

		} catch (JAXBException e) {
			e.printStackTrace();
		}
	}

}
